<?php

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use App\Services\FollowupService;
use App\Services\Replies\ReplyDetector;
use Illuminate\Support\Facades\Http;

function sentMessage(array $attributes = [], array $leadAttributes = []): OutreachMessage
{
    $lead = Lead::create($leadAttributes + ['business_name' => 'Lead '.uniqid(), 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'email' => 'owner@cafe.id']);
    $campaign = Campaign::firstOrCreate(['name' => 'Kampanye'], ['niche' => 'cafe', 'location' => 'Bali']);

    return OutreachMessage::create($attributes + [
        'lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'email', 'subject' => 'Penawaran',
        'message' => 'Pesan pertama', 'status' => 'sent', 'sent_at' => now()->subDays(4), 'message_id' => uniqid().'@example.com',
    ]);
}

test('follow-ups are created once for unanswered messages after N days', function () {
    Setting::put(['followup_enabled' => '1', 'followup_days' => '3']);
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse('Halo lagi, apakah sempat membaca pesan saya?'))]);

    $due = sentMessage();
    $recent = sentMessage(['sent_at' => now()->subDay()]);

    expect(app(FollowupService::class)->generateDue())->toBe(1);

    $followup = OutreachMessage::whereNotNull('followup_of_id')->first();
    expect($followup->followup_of_id)->toBe($due->id)
        ->and($followup->status)->toBe('pending')
        ->and($followup->subject)->toBe('Re: Penawaran')
        ->and($followup->message)->toContain('Halo lagi');

    // Tidak dibuat dua kali
    expect(app(FollowupService::class)->generateDue())->toBe(0);
});

test('follow-ups respect the setting, replies, and a template fallback when AI is unavailable', function () {
    $message = sentMessage();

    expect(app(FollowupService::class)->generateDue())->toBe(0); // nonaktif secara default

    Setting::put(['followup_enabled' => '1', 'followup_days' => '3']);
    expect(app(FollowupService::class)->generateDue())->toBe(1); // AI tidak dikonfigurasi → teks cadangan
    expect(OutreachMessage::whereNotNull('followup_of_id')->first()->mode)->toBe('template');

    $replied = sentMessage();
    $replied->markReplied();
    expect(app(FollowupService::class)->candidates(3)->whereKey($replied->id)->exists())->toBeFalse();
});

test('replies are matched by In-Reply-To header first', function () {
    $message = sentMessage(['message_id' => 'abc-123@example.com'], ['email' => 'someone@else.id']);

    $matched = app(ReplyDetector::class)->process([
        ['from' => 'different@sender.id', 'in_reply_to' => '<abc-123@example.com>', 'references' => '', 'date' => now()],
    ]);

    expect($matched)->toBe(1)
        ->and($message->fresh()->status)->toBe('replied')
        ->and($message->fresh()->replied_at)->not->toBeNull()
        ->and($message->lead->fresh()->pipeline_stage)->toBe('replied');
});

test('replies fall back to matching the sender address, ignoring older emails', function () {
    $message = sentMessage([], ['email' => 'Owner@Cafe.id']);

    $detector = app(ReplyDetector::class);

    // Email dari alamat yang sama tapi lebih tua dari pesan kita → bukan balasan
    expect($detector->process([['from' => 'owner@cafe.id', 'date' => now()->subDays(10)]]))->toBe(0);

    expect($detector->process([['from' => 'OWNER@cafe.id', 'date' => now()]]))->toBe(1);
    expect($message->fresh()->status)->toBe('replied');

    // Diproses ulang tidak menghitung dua kali
    expect($detector->process([['from' => 'owner@cafe.id', 'date' => now()]]))->toBe(0);
});

test('the reply checker command is a no-op while IMAP is disabled', function () {
    $this->artisan('outreach:check-replies')->expectsOutputToContain('IMAP nonaktif')->assertSuccessful();
});

test('a reply asking to stop blacklists the sender instead of counting as a reply', function () {
    $message = sentMessage([], ['email' => 'owner@cafe.id']);
    $queued = OutreachMessage::create($message->only(['lead_id', 'campaign_id', 'type', 'message']) + ['status' => 'queued', 'scheduled_at' => now()->addHour()]);

    $detector = app(ReplyDetector::class);
    $matched = $detector->process([[
        'from' => 'owner@cafe.id',
        'subject' => 'Re: Penawaran',
        'date' => now(),
        'body' => "BERHENTI\n\nPada Sen, 27 Sep 2026 Thoriq menulis:\n> Yth. Bapak/Ibu, jangan berhenti membaca...",
    ]]);

    expect($matched)->toBe(0)
        ->and($detector->unsubscribed)->toBe(1)
        ->and(\App\Models\BlacklistEntry::contains('email', 'owner@cafe.id'))->toBeTrue()
        ->and($message->fresh()->status)->toBe('sent')
        ->and($queued->fresh()->status)->toBe('failed')
        ->and($message->lead->fresh()->pipeline_stage)->toBe('lost');
});

test('unsubscribe detection ignores normal replies that quote our message', function () {
    $detector = app(ReplyDetector::class);

    expect($detector->isUnsubscribeRequest(['subject' => 'unsubscribe']))->toBeTrue()
        ->and($detector->isUnsubscribeRequest(['subject' => 'Re: Stop']))->toBeTrue()
        ->and($detector->isUnsubscribeRequest(['subject' => 'Re: Penawaran', 'body' => "Boleh, kirim portofolionya ya.\n> Balas BERHENTI jika tidak ingin dihubungi"]))->toBeFalse()
        ->and($detector->isUnsubscribeRequest(['subject' => 'Re: Penawaran', 'body' => str_repeat('Kami tertarik dan ingin diskusi lebih lanjut. ', 10).' stop']))->toBeFalse();
});
