<?php

use App\Jobs\SendOutreachJob;
use App\Mail\OutreachMail;
use App\Models\BlacklistEntry;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use App\Services\OutreachSender;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    loginAs();
    // Senin 10:00 WIB: di dalam jendela kirim default
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-28 10:00:00', 'Asia/Jakarta'));
});

function outreachMessage(array $attributes = [], array $leadAttributes = []): OutreachMessage
{
    $lead = Lead::create($leadAttributes + ['business_name' => 'Cafe '.uniqid(), 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'email' => 'owner@cafe.id', 'phone' => '0812-3456-789']);
    $campaign = Campaign::firstOrCreate(['name' => 'Kampanye'], ['niche' => 'cafe', 'location' => 'Bali']);

    return OutreachMessage::create($attributes + [
        'lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'email',
        'subject' => 'Halo', 'message' => 'Isi pesan', 'status' => 'pending',
    ]);
}

test('sending an email marks it sent, stores a message id and advances the lead stage', function () {
    Mail::fake();
    $message = outreachMessage();

    $this->postJson(route('outreach.send', $message))->assertOk()->assertJsonPath('status', 'success');

    $message->refresh();
    expect($message->status)->toBe('sent')
        ->and($message->sent_at)->not->toBeNull()
        ->and($message->message_id)->toEndWith('@example.com')
        ->and($message->lead->pipeline_stage)->toBe('contacted');

    Mail::assertSent(OutreachMail::class, fn ($mail) => $mail->hasTo('owner@cafe.id') && $mail->messageIdentifier === $message->message_id);
});

test('an email that was already sent is never sent again', function () {
    Mail::fake();
    $message = outreachMessage(['status' => 'sent', 'sent_at' => now()]);

    $this->postJson(route('outreach.send', $message))->assertStatus(422);

    Mail::assertNothingSent();
});

test('blacklisted recipients are not emailed', function () {
    Mail::fake();
    BlacklistEntry::add('email', 'OWNER@cafe.id', 'unsubscribe');
    $message = outreachMessage();

    $this->postJson(route('outreach.send', $message))->assertStatus(500);

    Mail::assertNothingSent();
    expect($message->fresh()->status)->toBe('failed')
        ->and($message->fresh()->last_error)->toContain('blacklist');
});

test('the log mailer is reported as not really sent', function () {
    config(['mail.default' => 'log']);
    $message = outreachMessage();

    $this->postJson(route('outreach.send', $message))->assertOk()->assertJsonPath('message', fn ($m) => str_contains($m, 'tidak benar-benar terkirim'));
});

test('whatsapp send redirects to wa.me with a normalised number', function () {
    $message = outreachMessage(['type' => 'whatsapp', 'subject' => null, 'message' => 'Halo & salam']);

    $this->post(route('outreach.send', $message))
        ->assertRedirect('https://wa.me/628123456789?text=Halo%20%26%20salam');

    expect($message->fresh()->status)->toBe('sent');
});

test('marking a message as sent manually fills sent_at, also in bulk', function () {
    $single = outreachMessage();
    $bulk = outreachMessage();

    $this->post(route('outreach.status', $single), ['status' => 'sent']);
    $this->post(route('outreach.bulk'), ['ids' => [$bulk->id], 'action' => 'status_sent']);

    expect($single->fresh()->sent_at)->not->toBeNull()
        ->and($bulk->fresh()->sent_at)->not->toBeNull();

    $this->post(route('outreach.status', $single), ['status' => 'replied']);
    expect($single->fresh()->replied_at)->not->toBeNull()
        ->and($single->lead->fresh()->pipeline_stage)->toBe('replied');
});

test('sent messages cannot be edited', function () {
    $message = outreachMessage(['status' => 'sent', 'sent_at' => now()]);

    $this->put(route('outreach.update', $message), ['message' => 'diubah'])->assertSessionHas('error');

    expect($message->fresh()->message)->toBe('Isi pesan');
});

test('bulk send queues emails with increasing random gaps and skips non-emails', function () {
    config(['leadhunter.sending.min_gap_seconds' => 60, 'leadhunter.sending.max_gap_seconds' => 120]);

    $a = outreachMessage();
    $b = outreachMessage();
    $c = outreachMessage();
    $wa = outreachMessage(['type' => 'whatsapp']);
    $noEmail = outreachMessage([], ['email' => null]);

    $this->post(route('outreach.bulk'), ['ids' => [$a->id, $b->id, $c->id, $wa->id, $noEmail->id], 'action' => 'send_queue'])
        ->assertSessionHas('success', fn ($msg) => str_contains($msg, '3 email (maks.') && str_contains($msg, '2 dilewati'));

    [$a, $b, $c] = [$a->fresh(), $b->fresh(), $c->fresh()];
    expect([$a->status, $b->status, $c->status])->toBe(['queued', 'queued', 'queued'])
        ->and($wa->fresh()->status)->toBe('pending');

    $gap1 = $a->scheduled_at->diffInSeconds($b->scheduled_at);
    $gap2 = $b->scheduled_at->diffInSeconds($c->scheduled_at);
    expect($gap1)->toBeGreaterThanOrEqual(60)->toBeLessThanOrEqual(120)
        ->and($gap2)->toBeGreaterThanOrEqual(60)->toBeLessThanOrEqual(120);
});

test('the scheduler dispatches due emails one per run within the hourly limit', function () {
    Queue::fake();
    config(['leadhunter.sending.hourly_limit' => 3]);

    // 1 email sudah terkirim dalam satu jam terakhir → sisa kuota 2
    outreachMessage(['status' => 'sent', 'sent_at' => now()->subMinutes(10)]);
    $due = collect(range(1, 3))->map(fn ($i) => outreachMessage(['status' => 'queued', 'scheduled_at' => now()->subMinutes(5 - $i)]));
    $future = outreachMessage(['status' => 'queued', 'scheduled_at' => now()->addHour()]);

    // Satu pesan per putaran: antrean yang tertunda tidak terkirim sekaligus
    $this->artisan('outreach:send-due')->assertSuccessful();
    Queue::assertPushed(SendOutreachJob::class, 1);
    expect($due[0]->fresh()->scheduled_at)->toBeNull()
        ->and($due[1]->fresh()->scheduled_at)->not->toBeNull();

    $this->artisan('outreach:send-due');
    Queue::assertPushed(SendOutreachJob::class, 2);

    // Pesan yang sedang diproses worker ikut dihitung → kuota habis
    $this->artisan('outreach:send-due');
    Queue::assertPushed(SendOutreachJob::class, 2);
    expect($due[2]->fresh()->scheduled_at)->not->toBeNull()
        ->and($future->fresh()->scheduled_at)->not->toBeNull();
});

test('the send job skips messages that were taken out of the queue', function () {
    Mail::fake();
    $cancelled = outreachMessage(['status' => 'pending']);
    $queued = outreachMessage(['status' => 'queued']);

    (new SendOutreachJob($cancelled->id))->handle(app(OutreachSender::class));
    (new SendOutreachJob($queued->id))->handle(app(OutreachSender::class));

    Mail::assertSentCount(1);
    expect($queued->fresh()->status)->toBe('sent')->and($cancelled->fresh()->status)->toBe('pending');
});

test('transient failures are retried, permanent ones are not', function () {
    $transient = outreachMessage(['status' => 'failed', 'attempts' => 1]);
    $permanent = outreachMessage(['status' => 'failed', 'attempts' => 3]);
    OutreachMessage::whereIn('id', [$transient->id, $permanent->id])->update(['updated_at' => now()->subHour()]);

    $this->artisan('outreach:retry-failed')->assertSuccessful();

    expect($transient->fresh()->status)->toBe('queued')
        ->and($permanent->fresh()->status)->toBe('failed');
});

test('the unsubscribe link blacklists the email and cancels queued mail', function () {
    $message = outreachMessage(['status' => 'sent', 'sent_at' => now()]);
    $queued = OutreachMessage::create($message->only(['lead_id', 'campaign_id', 'type', 'message']) + ['status' => 'queued', 'scheduled_at' => now()->addHour()]);

    auth()->logout();

    $this->get(route('unsubscribe', $message))->assertForbidden(); // tanpa tanda tangan

    $this->get(URL::signedRoute('unsubscribe', ['outreachMessage' => $message->id]))
        ->assertOk()
        ->assertSee('owner@cafe.id');

    expect(BlacklistEntry::contains('email', 'owner@cafe.id'))->toBeTrue()
        ->and($queued->fresh()->status)->toBe('failed')
        ->and($message->lead->fresh()->pipeline_stage)->toBe('lost');

    // One-click POST dari Gmail (tanpa CSRF token)
    $this->post(URL::signedRoute('unsubscribe', ['outreachMessage' => $message->id]))->assertNoContent();
});

test('local installs send a reply-to-unsubscribe instruction instead of a broken localhost link', function () {
    Mail::fake();
    config(['app.url' => 'http://localhost']);
    $message = outreachMessage();

    $this->postJson(route('outreach.send', $message))->assertOk();

    Mail::assertSent(OutreachMail::class, function ($mail) {
        $headers = $mail->headers()->text;

        return $mail->unsubscribeUrl === null
            && $headers['List-Unsubscribe'] === '<mailto:hello@example.com?subject=unsubscribe>'
            && str_contains($mail->render(), 'Balas email ini dengan kata');
    });
});

test('public installs include a signed one-click unsubscribe link', function () {
    Mail::fake();
    config(['app.url' => 'https://leadhunter.example.com']);
    URL::forceRootUrl('https://leadhunter.example.com');
    URL::forceScheme('https');
    $message = outreachMessage();

    $this->postJson(route('outreach.send', $message))->assertOk();

    Mail::assertSent(OutreachMail::class, fn ($mail) => str_starts_with($mail->unsubscribeUrl, 'https://leadhunter.example.com/unsubscribe/')
        && $mail->headers()->text['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click');
});

test('outreach emails use the company brand, escape the message and include a plain-text part', function () {
    Mail::fake();
    Setting::put(['company_name' => 'Lefateach', 'company_website' => 'lefateach.com', 'company_phone' => '0895-3655-00805', 'company_logo_url' => 'https://lefateach.com/images/logo.png']);
    $message = outreachMessage(['message' => "Halo <b>Cafe</b>,\n\nParagraf kedua."]);

    $this->postJson(route('outreach.send', $message))->assertOk();

    Mail::assertSent(OutreachMail::class, function ($mail) {
        $html = $mail->render();
        $content = $mail->content();
        $text = view($content->text, $content->with)->render();

        return str_contains($html, 'src="https://lefateach.com/images/logo.png"')
            // orb kaca disematkan sebagai gambar inline (CID → data URI saat render): 2 di header, 1 di footer
            && substr_count($html, 'src="data:image/png;base64,') === 3
            && str_contains($html, 'href="https://wa.me/62895365500805"')
            && str_contains($html, 'Halo &lt;b&gt;Cafe&lt;/b&gt;,</p>')
            && ! str_contains($html, '<b>Cafe</b>')
            && str_contains($text, 'Halo <b>Cafe</b>,')
            && str_contains($text, 'Website: https://lefateach.com')
            && str_contains($text, 'Balas email ini dengan kata BERHENTI');
    });
});

test('an office phone number is shown as a phone link without a WhatsApp button', function () {
    Mail::fake();
    Setting::put(['company_name' => 'Webku', 'company_phone' => '(031) 5964600']);

    $this->postJson(route('outreach.send', outreachMessage()))->assertOk();

    Mail::assertSent(OutreachMail::class, fn ($mail) => str_contains($mail->render(), 'href="tel:0315964600"')
        && ! str_contains($mail->render(), 'wa.me'));
});

test('the composer previews all leads with parallel AI calls and falls back per lead', function () {
    fakeAiConfigured();
    $ok = outreachMessage()->lead;
    $failing = outreachMessage()->lead;

    Http::fake(function ($request) use ($failing) {
        return str_contains($request['messages'][1]['content'], $failing->business_name)
            ? Http::response(['error' => 'overloaded'], 503)
            : Http::response(aiResponse('Yth. Pimpinan, pesan AI.'));
    });

    $response = $this->postJson(route('outreach.compose.preview'), [
        'lead_ids' => [$ok->id, $failing->id], 'mode' => 'ai', 'type' => 'email',
    ])->assertOk();

    $previews = collect($response->json('previews'))->keyBy('lead_id');
    expect($previews[$ok->id]['message'])->toStartWith("Yth. Pimpinan, pesan AI.

Salam,")
        ->and($previews[$ok->id]['is_fallback'])->toBeFalse()
        ->and($previews[$failing->id]['is_fallback'])->toBeTrue()
        ->and($previews[$failing->id]['fallback_reason'])->toContain('503');

    Http::assertSentCount(2);
});
