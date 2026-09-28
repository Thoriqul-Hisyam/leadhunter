<?php

use App\Exceptions\OutreachSendException;
use App\Models\BlacklistEntry;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;
use App\Services\FollowupService;
use App\Services\OutreachSender;
use App\Services\Replies\ReplyDetector;
use App\Services\Replies\WhatsAppReplyHandler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    loginAs();
    $this->travelTo(Carbon::parse('2026-09-28 10:00:00', 'Asia/Jakarta')); // Senin, dalam jam kirim
});

function sequenceCampaign(array $steps = []): Campaign
{
    $campaign = Campaign::create(['name' => 'Klinik Surabaya', 'niche' => 'klinik', 'location' => 'Surabaya']);

    foreach ($steps as $i => $step) {
        $campaign->steps()->create(['step' => $i + 2] + $step);
    }

    return $campaign;
}

function openingEmail(Campaign $campaign, array $leadAttributes = [], array $attributes = []): OutreachMessage
{
    $lead = Lead::create($leadAttributes + [
        'business_name' => 'Klinik Sehat', 'niche' => 'klinik', 'city' => 'Surabaya', 'source' => 'manual',
        'email' => 'halo@klinik.id', 'phone' => '0812 3456 7890',
    ]);

    return OutreachMessage::create($attributes + [
        'lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'email', 'subject' => 'website untuk Klinik Sehat?',
        'message' => 'Pesan pembuka', 'status' => 'sent', 'sent_at' => now()->subDays(3), 'message_id' => uniqid().'@leadhunter.test',
    ]);
}

/*
|--------------------------------------------------------------------------
| Sequence
|--------------------------------------------------------------------------
*/

test('a campaign sequence runs email → whatsapp → closing email, one step at a time', function () {
    $campaign = sequenceCampaign([
        ['channel' => 'whatsapp', 'delay_days' => 3],
        ['channel' => 'email', 'delay_days' => 4],
    ]);
    $opening = openingEmail($campaign);
    $service = app(FollowupService::class);

    expect($service->generateDue())->toBe(1);

    $wa = OutreachMessage::where('step', 2)->first();
    expect($wa->type)->toBe('whatsapp')
        ->and($wa->followup_of_id)->toBe($opening->id)
        ->and($wa->status)->toBe('pending')
        ->and($wa->subject)->toBeNull();

    // Langkah 3 menunggu langkah 2 terkirim
    expect($service->generateDue())->toBe(0);

    $wa->markSent();
    $this->travel(3)->days();
    expect($service->generateDue())->toBe(0); // jeda langkah 3 = 4 hari

    $this->travel(1)->days();
    expect($service->generateDue())->toBe(1);

    $closing = OutreachMessage::where('step', 3)->first();
    expect($closing->type)->toBe('email')
        ->and($closing->followup_of_id)->toBe($wa->id)
        ->and($closing->subject)->toBe('Re: website untuk Klinik Sehat?')
        ->and($closing->message)->toContain('pesan terakhir');

    $closing->markSent();
    $this->travel(10)->days();
    expect($service->generateDue())->toBe(0); // sequence selesai
});

test('cross-channel and closing steps tell the ai what happened before', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse('Halo, kemarin saya sempat kirim email soal website.'))]);

    $campaign = sequenceCampaign([['channel' => 'whatsapp', 'delay_days' => 3], ['channel' => 'email', 'delay_days' => 2]]);
    openingEmail($campaign);

    app(FollowupService::class)->generateDue();

    Http::assertSent(fn ($r) => str_contains($r['messages'][1]['content'], 'Tulis pesan WhatsApp follow-up')
        && str_contains($r['messages'][1]['content'], 'sempat mengirim email')
        && ! str_contains($r['messages'][1]['content'], 'pesan terakhir'));

    expect(OutreachMessage::where('step', 2)->value('mode'))->toBe('ai');
});

test('a step falls back to the previous channel when the lead cannot be reached there', function () {
    $campaign = sequenceCampaign([['channel' => 'whatsapp', 'delay_days' => 3]]);
    openingEmail($campaign, ['phone' => '(031) 5964600']); // telepon kantor, bukan WA

    app(FollowupService::class)->generateDue();

    expect(OutreachMessage::where('step', 2)->value('type'))->toBe('email');
});

test('auto-queued steps go straight into the send queue', function () {
    $campaign = sequenceCampaign([['channel' => 'same', 'delay_days' => 3, 'auto_queue' => true]]);
    openingEmail($campaign);

    app(FollowupService::class)->generateDue();

    $step = OutreachMessage::where('step', 2)->first();
    expect($step->status)->toBe('queued')->and($step->scheduled_at)->not->toBeNull();
    expect(ScrapingNotification::latest('id')->value('message'))->toContain('langsung masuk antrean');
});

test('a reply on any channel stops the sequence, even for steps already queued', function () {
    Mail::fake();
    $campaign = sequenceCampaign([['channel' => 'same', 'delay_days' => 3]]);
    $opening = openingEmail($campaign);
    $queued = OutreachMessage::create($opening->only(['lead_id', 'campaign_id', 'type', 'subject']) + [
        'message' => 'Follow-up', 'status' => 'queued', 'scheduled_at' => now(), 'step' => 2, 'followup_of_id' => $opening->id,
    ]);

    // Lead membalas lewat WhatsApp (campaign lain)
    OutreachMessage::create(['lead_id' => $opening->lead_id, 'campaign_id' => sequenceCampaign()->id, 'type' => 'whatsapp', 'message' => 'Halo', 'status' => 'sent', 'sent_at' => now()->subDay()])->markReplied();

    expect(fn () => app(OutreachSender::class)->sendEmail($queued))->toThrow(OutreachSendException::class, 'Sequence dihentikan');
    expect($queued->fresh()->status)->toBe('failed');
    Mail::assertNothingSent();

    $other = openingEmail($campaign, ['business_name' => 'Klinik Lain', 'email' => 'lain@klinik.id']);
    $other->lead->update(['pipeline_stage' => 'meeting']); // stage diubah manual
    expect(app(FollowupService::class)->generateDue())->toBe(0);
});

test('the sequence can be edited on the campaign page', function () {
    $campaign = sequenceCampaign();

    $this->put(route('campaigns.sequence', $campaign), ['steps' => [
        ['channel' => 'whatsapp', 'delay_days' => 3, 'auto_queue' => '1'],
        ['channel' => 'email', 'delay_days' => 4],
    ]])->assertRedirect(route('campaigns.show', $campaign).'#sequence');

    expect($campaign->steps()->get()->map->only(['step', 'channel', 'delay_days', 'auto_queue'])->all())->toBe([
        ['step' => 2, 'channel' => 'whatsapp', 'delay_days' => 3, 'auto_queue' => true],
        ['step' => 3, 'channel' => 'email', 'delay_days' => 4, 'auto_queue' => false],
    ]);

    $this->get(route('campaigns.show', $campaign))->assertOk()->assertSee('Sequence follow-up')->assertSee('Langsung antrekan');

    $this->put(route('campaigns.sequence', $campaign), ['steps' => [['channel' => 'email', 'delay_days' => 0]]])->assertSessionHasErrors('steps.0.delay_days');
    $this->put(route('campaigns.sequence', $campaign), ['steps' => array_fill(0, 5, ['channel' => 'email', 'delay_days' => 2])])->assertSessionHasErrors('steps');

    $this->put(route('campaigns.sequence', $campaign))->assertSessionHas('success', fn ($m) => str_contains($m, 'follow-up default'));
    expect($campaign->steps()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Bounce & balasan otomatis
|--------------------------------------------------------------------------
*/

test('a gmail bounce fails the message and blacklists the address instead of counting as a reply', function () {
    $message = openingEmail(sequenceCampaign());
    $queued = OutreachMessage::create($message->only(['lead_id', 'campaign_id', 'type', 'subject']) + ['message' => 'Lagi', 'status' => 'queued', 'scheduled_at' => now()->addHour()]);

    $detector = app(ReplyDetector::class);
    $matched = $detector->process([[
        'from' => 'mailer-daemon@googlemail.com',
        'subject' => 'Delivery Status Notification (Failure)',
        'in_reply_to' => '<'.$message->message_id.'>',
        'date' => now(),
        'body' => "Address not found\nYour message wasn't delivered to halo@klinik.id because the address couldn't be found.",
    ]]);

    expect($matched)->toBe(0)
        ->and($detector->bounced)->toBe(1)
        ->and($message->fresh()->status)->toBe('failed')
        ->and($message->fresh()->last_error)->toContain('Bounce')
        ->and(BlacklistEntry::where('value', 'halo@klinik.id')->value('reason'))->toBe('bounce')
        ->and($queued->fresh()->status)->toBe('failed');
});

test('bounces without headers are matched by the recipient address, delays are ignored', function () {
    $message = openingEmail(sequenceCampaign());
    $detector = app(ReplyDetector::class);

    // Penundaan sementara: bukan bounce, dan bukan balasan
    $detector->process([['from' => 'mailer-daemon@googlemail.com', 'subject' => 'Delivery Status Notification (Delay)', 'in_reply_to' => $message->message_id, 'date' => now()]]);
    expect($message->fresh()->status)->toBe('sent')->and($detector->bounced)->toBe(0);

    $detector->process([[
        'from' => 'postmaster@mail.klinik.id',
        'subject' => 'Undeliverable: website untuk Klinik Sehat?',
        'date' => now(),
        'body' => "Delivery has failed to these recipients or groups:\nhalo@klinik.id\nThe mailbox is full.",
    ]]);

    expect($message->fresh()->status)->toBe('failed')->and($detector->bounced)->toBe(1);
});

test('out-of-office emails are not counted as replies', function () {
    $message = openingEmail(sequenceCampaign());
    $detector = app(ReplyDetector::class);

    expect($detector->process([['from' => 'halo@klinik.id', 'subject' => 'Automatic reply: website untuk Klinik Sehat?', 'date' => now(), 'body' => 'Saya sedang cuti sampai 5 Oktober.']]))->toBe(0)
        ->and($detector->autoReplies)->toBe(1)
        ->and($message->fresh()->status)->toBe('sent')
        ->and($message->fresh()->reply_category)->toBe('auto_reply')
        ->and($message->lead->fresh()->pipeline_stage)->not->toBe('replied');

    // Balasan manusia sesudahnya tetap terdeteksi
    expect($detector->process([['from' => 'halo@klinik.id', 'subject' => 'Re: website untuk Klinik Sehat?', 'date' => now(), 'body' => 'Boleh, kirim contohnya ya.']]))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Klasifikasi balasan
|--------------------------------------------------------------------------
*/

test('replies are classified by keywords when ai is not connected', function () {
    $message = openingEmail(sequenceCampaign([['channel' => 'same', 'delay_days' => 3]]));
    $pendingStep = OutreachMessage::create($message->only(['lead_id', 'campaign_id', 'type']) + ['message' => 'Follow-up', 'status' => 'pending', 'step' => 2, 'followup_of_id' => $message->id]);

    app(ReplyDetector::class)->process([[
        'from' => 'halo@klinik.id',
        'subject' => 'Re: website untuk Klinik Sehat?',
        'date' => now(),
        'body' => "Boleh, berapa harganya untuk website klinik?\n\nPada Sen, 28 Sep 2026 Thoriq menulis:\n> Pesan pembuka",
    ]]);

    $message->refresh();
    expect($message->status)->toBe('replied')
        ->and($message->reply_category)->toBe('pricing')
        ->and($message->reply_excerpt)->toBe('Boleh, berapa harganya untuk website klinik?')
        ->and($pendingStep->fresh()->status)->toBe('failed')
        ->and(ScrapingNotification::where('title', 'Lead Tanya Harga')->exists())->toBeTrue();
});

test('a "not interested" whatsapp reply closes the lead', function () {
    $lead = Lead::create(['business_name' => 'Cafe Kopi', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'phone' => '0812 3456 7890']);
    $message = OutreachMessage::create(['lead_id' => $lead->id, 'campaign_id' => sequenceCampaign()->id, 'type' => 'whatsapp', 'message' => 'Halo', 'status' => 'sent', 'sent_at' => now()->subDay()]);

    expect(app(WhatsAppReplyHandler::class)->handleMessage('6281234567890', 'Maaf, kami sudah punya website sendiri.'))->toBe('replied');

    expect($message->fresh()->reply_category)->toBe('not_interested')
        ->and($lead->fresh()->pipeline_stage)->toBe('lost');
});

test('whatsapp business greetings are recognised as auto-replies', function () {
    $lead = Lead::create(['business_name' => 'Cafe Kopi', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'phone' => '0812 3456 7890']);
    $message = OutreachMessage::create(['lead_id' => $lead->id, 'campaign_id' => sequenceCampaign()->id, 'type' => 'whatsapp', 'message' => 'Halo', 'status' => 'sent', 'sent_at' => now()->subDay()]);
    $handler = app(WhatsAppReplyHandler::class);

    expect($handler->handleMessage('6281234567890', 'Terima kasih telah menghubungi Cafe Kopi. Kami akan segera membalas pesan Anda.'))->toBe('auto_reply')
        ->and($handler->handleMessage('6281234567890', 'Halo kak, ketik 1 untuk menu, ketik 2 untuk reservasi.'))->toBe('auto_reply');

    expect($message->fresh()->status)->toBe('sent')->and($message->fresh()->reply_category)->toBe('auto_reply');

    expect($handler->handleMessage('6281234567890', 'Iya mas, boleh dijelaskan dulu?'))->toBe('replied');
    expect($message->fresh()->reply_category)->toBe('interested');
});

test('the ai classifies replies and an ai-detected auto-reply is not counted', function () {
    fakeAiConfigured();
    Http::fakeSequence('ai.test/*')
        ->push(aiResponse('interested'))
        ->push(aiResponse('Kategori: auto_reply'));

    $first = openingEmail(sequenceCampaign());
    $second = openingEmail(sequenceCampaign(), ['business_name' => 'Klinik Dua', 'email' => 'dua@klinik.id']);
    $detector = app(ReplyDetector::class);

    $detector->process([['from' => 'halo@klinik.id', 'subject' => 'Re: website', 'date' => now(), 'body' => 'Menarik juga, bisa kirim contoh websitenya?']]);
    expect($first->fresh()->reply_category)->toBe('interested')->and($first->fresh()->status)->toBe('replied');

    $detector->process([['from' => 'dua@klinik.id', 'subject' => 'Re: website', 'date' => now(), 'body' => 'Pesan Anda sudah kami terima dan akan diteruskan ke bagian terkait.']]);
    $second->refresh();
    expect($second->reply_category)->toBe('auto_reply')
        ->and($second->status)->toBe('sent')
        ->and($second->replied_at)->toBeNull()
        ->and($second->lead->fresh()->pipeline_stage)->toBe('contacted');

    Http::assertSent(fn ($r) => str_contains($r['messages'][1]['content'], 'Klasifikasikan balasan') && $r['temperature'] === 0);
});

test('an unsubscribe on one channel cancels pending messages on every channel', function () {
    $message = openingEmail(sequenceCampaign());
    $whatsapp = OutreachMessage::create(['lead_id' => $message->lead_id, 'campaign_id' => $message->campaign_id, 'type' => 'whatsapp', 'message' => 'Halo', 'status' => 'queued', 'scheduled_at' => now()->addHour()]);

    app(ReplyDetector::class)->process([['from' => 'halo@klinik.id', 'subject' => 'unsubscribe', 'date' => now()]]);

    expect($whatsapp->fresh()->status)->toBe('failed')->and($whatsapp->fresh()->last_error)->toContain('berhenti');
});

/*
|--------------------------------------------------------------------------
| Analitik
|--------------------------------------------------------------------------
*/

test('the dashboard compares reply rates per step and per subject pattern', function () {
    $campaign = sequenceCampaign();
    $a = openingEmail($campaign, ['business_name' => 'Klinik Alfa', 'email' => 'a@klinik.id'], ['subject' => 'website untuk Klinik Alfa?']);
    openingEmail($campaign, ['business_name' => 'Klinik Beta', 'email' => 'b@klinik.id'], ['subject' => 'website untuk Klinik Beta?']);
    $a->markReplied();
    OutreachMessage::create($a->only(['lead_id', 'campaign_id', 'type']) + ['message' => 'FU', 'status' => 'sent', 'sent_at' => now(), 'step' => 2, 'followup_of_id' => $a->id]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Reply Rate per Langkah')
        ->assertSee('Langkah 2 · follow-up')
        ->assertSee('website untuk {nama}?')
        ->assertSeeInOrder(['website untuk {nama}?', '50%', '1/2']);
});
