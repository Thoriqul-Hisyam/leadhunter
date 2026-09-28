<?php

use App\Helpers\Phone;
use App\Jobs\SendOutreachJob;
use App\Models\BlacklistEntry;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use App\Services\OutreachSender;
use App\Services\RuntimeConfig;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    loginAs('admin');
    // Senin 10:00 WIB: di dalam jendela kirim default
    $this->travelTo(Carbon::parse('2026-09-28 10:00:00', 'Asia/Jakarta'));
});

function waMessage(array $attributes = [], array $leadAttributes = []): OutreachMessage
{
    $lead = Lead::create($leadAttributes + ['business_name' => 'Klinik '.uniqid(), 'niche' => 'klinik', 'city' => 'Surabaya', 'source' => 'manual', 'phone' => '0812-3456-7890']);
    $campaign = Campaign::firstOrCreate(['name' => 'WA Campaign'], ['niche' => 'klinik', 'location' => 'Surabaya']);

    return OutreachMessage::create($attributes + [
        'lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'whatsapp', 'message' => 'Halo, boleh saya kirim contoh?', 'status' => 'pending',
    ]);
}

function useFonnte(): void
{
    Setting::put(['wa_driver' => 'fonnte', 'wa_token' => 'fonnte-token', 'wa_webhook_token' => 'rahasia-webhook']);
    RuntimeConfig::apply();
}

test('mobile numbers are told apart from landlines', function (?string $phone, bool $mobile) {
    expect(Phone::isMobile($phone))->toBe($mobile);
})->with([
    ['0812-3456-7890', true],
    ['+62 857 1234 5678', true],
    ['(031) 5964600', false],
    ['021 7654321', false],
    ['123', false],
    [null, false],
]);

test('leads keep a normalised whatsapp number in sync with their phone', function () {
    $lead = Lead::create(['business_name' => 'X', 'niche' => 'x', 'city' => 'Y', 'source' => 'manual', 'phone' => '(031) 5964600']);
    expect($lead->whatsapp_number)->toBe('62315964600')->and($lead->phone_is_mobile)->toBeFalse();

    $lead->update(['phone' => '0812 3456 7890']);
    expect($lead->fresh()->whatsapp_number)->toBe('6281234567890')->and($lead->fresh()->phone_is_mobile)->toBeTrue();
});

test('whatsapp messages are sent through fonnte and marked sent', function () {
    useFonnte();
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['80367170'], 'process' => 'pending'])]);
    $message = waMessage();

    $this->postJson(route('outreach.send', $message))->assertOk()->assertJsonPath('status', 'success');

    $message->refresh();
    expect($message->status)->toBe('sent')
        ->and($message->message_id)->toBe('80367170')
        ->and($message->lead->pipeline_stage)->toBe('contacted');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
        && $request->hasHeader('Authorization', 'fonnte-token')
        && $request['target'] === '6281234567890');
});

test('wablas uses the account server url', function () {
    Setting::put(['wa_driver' => 'wablas', 'wa_token' => 'wablas-token', 'wa_base_url' => 'https://tegal.wablas.com/']);
    RuntimeConfig::apply();
    Http::fake(['tegal.wablas.com/*' => Http::response(['status' => true, 'data' => ['messages' => [['id' => 'wb-1']]]])]);

    app(OutreachSender::class)->sendWhatsApp($message = waMessage());

    expect($message->fresh()->message_id)->toBe('wb-1');
    Http::assertSent(fn ($request) => $request->url() === 'https://tegal.wablas.com/api/send-message' && $request['phone'] === '6281234567890');
});

test('numbers not on whatsapp fail permanently, other errors can be retried', function () {
    useFonnte();
    Http::fake(['api.fonnte.com/*' => Http::sequence()
        ->push(['status' => false, 'reason' => 'target invalid, not registered on whatsapp'])
        ->push(['status' => false, 'reason' => 'device disconnected'])]);

    $notOnWa = waMessage();
    $temporary = waMessage();

    $this->postJson(route('outreach.send', $notOnWa))->assertStatus(422);
    $this->postJson(route('outreach.send', $temporary))->assertStatus(422);

    expect($notOnWa->fresh()->attempts)->toBe(3)
        ->and($temporary->fresh()->attempts)->toBe(1)
        ->and($temporary->fresh()->last_error)->toContain('device disconnected');
});

test('landlines, blacklisted numbers and repeat messages are not sent automatically', function () {
    useFonnte();
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['1']])]);

    $landline = waMessage([], ['phone' => '(031) 5964600']);
    $blocked = waMessage([], ['phone' => '0899 1111 2222']);
    BlacklistEntry::add('phone', '0899 1111 2222');
    $first = waMessage(['status' => 'sent', 'sent_at' => now()], ['phone' => '0877 1234 5678']);
    $repeat = OutreachMessage::create($first->only(['lead_id', 'campaign_id', 'type', 'message']) + ['status' => 'pending']);

    foreach ([$landline, $blocked, $repeat] as $message) {
        $this->postJson(route('outreach.send', $message))->assertStatus(422);
    }

    Http::assertNothingSent();
    expect($landline->fresh()->last_error)->toContain('telepon kantor')
        ->and($repeat->fresh()->last_error)->toContain('sudah menerima WhatsApp');

    // Nomor kantor boleh jika diizinkan di Pengaturan
    Setting::put(['wa_allow_landline' => '1']);
    RuntimeConfig::apply();
    $landline->update(['status' => 'pending', 'attempts' => 0]);
    $this->postJson(route('outreach.send', $landline))->assertOk();
});

test('manual mode keeps click-to-chat', function () {
    $message = waMessage(['message' => 'Halo & salam']);

    $this->post(route('outreach.send', $message))->assertRedirect('https://wa.me/6281234567890?text=Halo%20%26%20salam');
});

test('bulk queue schedules email and whatsapp on separate timelines with whatsapp limits', function () {
    useFonnte();
    config(['leadhunter.whatsapp.min_gap_seconds' => 300, 'leadhunter.whatsapp.max_gap_seconds' => 300]);

    $wa1 = waMessage();
    $wa2 = waMessage();
    $landline = waMessage([], ['phone' => '(031) 5964600']);

    $this->post(route('outreach.bulk'), ['ids' => [$wa1->id, $wa2->id, $landline->id], 'action' => 'send_queue'])
        ->assertSessionHas('success', fn ($m) => str_contains($m, '2 WhatsApp (maks. 10/jam, 50/hari)') && str_contains($m, '1 dilewati'));

    expect($wa1->fresh()->scheduled_at->diffInSeconds($wa2->fresh()->scheduled_at))->toEqual(300.0)
        ->and($landline->fresh()->status)->toBe('pending');
});

test('queued messages outside the sending window move to the next window', function () {
    $this->travelTo(Carbon::parse('2026-10-02 17:30:00', 'Asia/Jakarta')); // Jumat sore
    $sender = app(OutreachSender::class);

    expect($sender->nextSendableTime(now())->toDateTimeString())->toBe('2026-10-05 08:00:00') // Senin pagi
        ->and($sender->withinWindow(now()))->toBeFalse();

    Queue::fake();
    $message = waMessage(['type' => 'email', 'subject' => 'x'], ['email' => 'a@b.id']);
    $sender->queue(collect([$message]));
    expect($message->fresh()->scheduled_at->toDateTimeString())->toBe('2026-10-05 08:00:00');

    $this->artisan('outreach:send-due');
    Queue::assertNothingPushed();
});

test('the scheduler sends at most one message per channel per run and respects daily limits', function () {
    useFonnte();
    Queue::fake();
    config(['leadhunter.whatsapp.daily_limit' => 2]);

    waMessage(['status' => 'sent', 'sent_at' => now()->subHours(3)]);
    $due = collect(range(1, 3))->map(fn () => waMessage(['status' => 'queued', 'scheduled_at' => now()->subMinute()]));

    $this->artisan('outreach:send-due');
    Queue::assertPushed(SendOutreachJob::class, 1);

    // Pesan pertama sedang diproses worker → ikut dihitung; sisa kuota harian 0
    $this->artisan('outreach:send-due');
    Queue::assertPushed(SendOutreachJob::class, 1);
    expect($due->filter(fn ($m) => $m->fresh()->scheduled_at === null)->count())->toBe(1);
});

test('the webhook records whatsapp replies and stop requests', function () {
    useFonnte();
    $message = waMessage(['status' => 'sent', 'sent_at' => now()->subDay()]);
    $other = waMessage(['status' => 'sent', 'sent_at' => now()->subDay()], ['phone' => '0857 0000 1111']);
    $queued = OutreachMessage::create($other->only(['lead_id', 'campaign_id', 'type', 'message']) + ['status' => 'queued', 'step' => 2, 'scheduled_at' => now()->addDay()]);

    auth()->logout();

    $this->postJson('/webhooks/whatsapp/salah-token', ['sender' => '6281234567890', 'message' => 'Halo'])->assertNotFound();

    $this->postJson('/webhooks/whatsapp/rahasia-webhook', ['sender' => '6281234567890', 'message' => 'Boleh, kirim contohnya ya'])
        ->assertOk()->assertJsonPath('replied', 1);

    $this->post('/webhooks/whatsapp/rahasia-webhook', ['sender' => '085700001111', 'message' => 'STOP'])
        ->assertOk()->assertJsonPath('unsubscribed', 1);

    expect($message->fresh()->status)->toBe('replied')
        ->and($message->fresh()->reply_excerpt)->toBe('Boleh, kirim contohnya ya')
        ->and(BlacklistEntry::contains('phone', '085700001111'))->toBeTrue()
        ->and($queued->fresh()->status)->toBe('failed')
        ->and($other->lead->fresh()->pipeline_stage)->toBe('lost');
});

test('the webhook marks messages failed from gateway status reports', function () {
    useFonnte();
    $message = waMessage(['status' => 'sent', 'sent_at' => now(), 'message_id' => '555']);

    $this->postJson('/webhooks/whatsapp/rahasia-webhook', ['id' => '555', 'status' => 'failed'])->assertOk()->assertJsonPath('failed', 1);

    expect($message->fresh()->status)->toBe('failed');
});

test('whatsapp gateway and sending rules are managed in settings', function () {
    $this->put(route('settings.sending'), [
        'wa_driver' => 'fonnte', 'wa_token' => 'tok-123', 'wa_hourly_limit' => 8, 'wa_daily_limit' => 40,
        'email_hourly_limit' => 15, 'email_daily_limit' => 60, 'send_window_start' => '09:00', 'send_window_end' => '15:00',
        'send_weekdays_only' => '1',
    ])->assertRedirect();

    RuntimeConfig::apply();

    expect(Setting::get('wa_token'))->toBe('tok-123')
        ->and(Setting::find('wa_token')->value)->not->toContain('tok-123')
        ->and(config('leadhunter.whatsapp.driver'))->toBe('fonnte')
        ->and(config('leadhunter.whatsapp.daily_limit'))->toBe(40)
        ->and(config('leadhunter.sending.hourly_limit'))->toBe(15)
        ->and(config('leadhunter.sending.window_start'))->toBe('09:00')
        ->and(Setting::get('wa_webhook_token'))->toHaveLength(40);

    $this->get(route('settings.edit'))->assertSee('/webhooks/whatsapp/'.Setting::get('wa_webhook_token'))->assertDontSee('tok-123');

    // Tes kirim dari Pengaturan
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['9']])]);
    $this->post(route('settings.test-whatsapp'), ['number' => '0812 3456 7890'])->assertSessionHas('success');
    expect(Setting::get('wa_tested_at'))->not->toBe('');
});

test('gateways other than manual require a token', function () {
    $this->put(route('settings.sending'), [
        'wa_driver' => 'fonnte', 'wa_hourly_limit' => 8, 'wa_daily_limit' => 40,
        'email_hourly_limit' => 15, 'email_daily_limit' => 60, 'send_window_start' => '09:00', 'send_window_end' => '15:00',
    ])->assertSessionHasErrors('wa_token');
});
