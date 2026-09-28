<?php

use App\Exceptions\AiException;
use App\Jobs\GenerateOutreachJob;
use App\Models\AiUsageLog;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\MessageQualityGate;
use App\Services\OutreachSender;
use App\Services\RuntimeConfig;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => loginAs('admin'));

function withBackupAi(): void
{
    fakeAiConfigured();
    config([
        'services.ai.backup.base_url' => 'https://backup.test/v1',
        'services.ai.backup.key' => 'backup-key',
        'services.ai.backup.model' => 'backup-model',
    ]);
}

function reliabilityLead(array $attributes = []): Lead
{
    return Lead::create($attributes + ['business_name' => 'Klinik Gigi Sehat', 'niche' => 'klinik gigi', 'city' => 'Surabaya', 'source' => 'manual', 'email' => 'halo@klinik.id']);
}

const GOOD_EMAIL = "SUBJEK: website untuk klinik gigi sehat?\n\nSelamat siang, tim Klinik Gigi Sehat.\n\nSaya lihat klinik ini cukup sering dicari di Google Maps, tapi belum ada website yang bisa dibuka calon pasien untuk melihat layanan dan jadwal praktik.\n\nKami biasa membuatkan website sederhana untuk klinik gigi, lengkap dengan tombol janji temu lewat WhatsApp.\n\nBoleh saya kirimkan contoh tampilannya?";

/*
|--------------------------------------------------------------------------
| 9.1 Log pemakaian
|--------------------------------------------------------------------------
*/

test('every ai call is logged with feature, latency and token usage', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::sequence()
        ->push(aiResponse('OK') + ['usage' => ['prompt_tokens' => 120, 'completion_tokens' => 8, 'total_tokens' => 128]])
        ->push(['error' => 'boom'], 500)]);

    $ai = app(AiService::class);
    $ai->generateText('tes', ['feature' => 'classify']);
    expect(fn () => $ai->generateText('tes', ['feature' => 'outreach']))->toThrow(AiException::class);

    [$ok, $failed] = AiUsageLog::orderBy('id')->get()->all();
    expect($ok->only(['feature', 'provider', 'model', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'success']))->toBe([
        'feature' => 'classify', 'provider' => 'primary', 'model' => 'test-model',
        'prompt_tokens' => 120, 'completion_tokens' => 8, 'total_tokens' => 128, 'success' => true,
    ])
        ->and($ok->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($failed->success)->toBeFalse()
        ->and($failed->error)->toContain('500');
});

test('the settings page summarises ai usage per day and per feature', function () {
    AiUsageLog::record(['feature' => 'outreach', 'provider' => 'primary', 'model' => 'm', 'duration_ms' => 42000, 'total_tokens' => 900, 'success' => true]);
    AiUsageLog::record(['feature' => 'outreach', 'provider' => 'backup', 'model' => 'm', 'duration_ms' => 8000, 'total_tokens' => 700, 'success' => true]);
    AiUsageLog::record(['feature' => 'classify', 'provider' => 'primary', 'model' => 'm', 'duration_ms' => 1000, 'success' => false, 'error' => 'timeout']);

    $today = AiUsageLog::dailySummary()->last();
    expect($today)->toMatchArray(['calls' => 3, 'failed' => 1, 'backup' => 1, 'avg_seconds' => 25.0, 'tokens' => 1600]);

    $this->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('Pemakaian AI (7 hari)')
        ->assertSee('Pesan outreach')
        ->assertSee('Klasifikasi balasan')
        ->assertSee('25 dtk');
});

test('old ai logs are pruned by the cleanup command', function () {
    AiUsageLog::create(['feature' => 'outreach', 'provider' => 'primary', 'duration_ms' => 1, 'success' => true, 'created_at' => now()->subDays(120)]);
    AiUsageLog::record(['feature' => 'outreach', 'provider' => 'primary', 'duration_ms' => 1, 'success' => true]);

    $this->artisan('leadhunter:cleanup')->expectsOutputToContain('1 log AI lama')->assertSuccessful();
    expect(AiUsageLog::count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| 9.2 Provider cadangan & model cepat
|--------------------------------------------------------------------------
*/

test('the backup provider takes over when the primary fails', function () {
    withBackupAi();
    Http::fake([
        'ai.test/*' => Http::response(['error' => 'upstream down'], 503),
        'backup.test/*' => Http::response(aiResponse('Dari cadangan')),
    ]);

    expect(app(AiService::class)->generateText('tes', ['feature' => 'outreach']))->toBe('Dari cadangan');

    Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://backup.test/v1/chat/completions')
        && $r['model'] === 'backup-model'
        && $r->hasHeader('Authorization', 'Bearer backup-key'));

    expect(AiUsageLog::orderBy('id')->pluck('provider')->all())->toBe(['primary', 'backup'])
        ->and(AiUsageLog::orderBy('id')->pluck('success')->all())->toBe([false, true]);
});

test('parallel generation retries only the failed prompts on the backup provider', function () {
    withBackupAi();
    Http::fake([
        'ai.test/*' => Http::sequence()->push(aiResponse('A dari utama'))->push(['error' => 'rate limited'], 429),
        'backup.test/*' => Http::response(aiResponse('B dari cadangan')),
    ]);

    $results = app(AiService::class)->generateMany(['a' => 'prompt a', 'b' => 'prompt b'], ['feature' => 'outreach'], concurrency: 1);

    expect($results)->toBe(['a' => 'A dari utama', 'b' => 'B dari cadangan']);
    Http::assertSentCount(3);
});

test('short-answer features use the fast model on the primary provider', function () {
    fakeAiConfigured();
    config(['services.ai.fast_model' => 'fast-model']);
    Http::fake(['ai.test/*' => Http::response(aiResponse('interested'))]);

    $ai = app(AiService::class);
    $ai->classifyReply('Boleh, kirim contohnya.');
    $ai->generateText('tulis pesan', ['feature' => 'outreach']);

    $models = collect(Http::recorded())->map(fn ($pair) => $pair[0]['model'])->all();
    expect($models)->toBe(['fast-model', 'test-model']);
});

test('backup provider settings are stored encrypted and never rendered', function () {
    $this->put(route('settings.connections'), connectionPayload([
        'ai_fast_model' => 'llama-3.1-8b-instant',
        'ai_backup_base_url' => 'https://api.groq.com/openai/v1/',
        'ai_backup_api_key' => 'gsk-rahasia-cadangan',
        'ai_backup_model' => 'llama-3.3-70b-versatile',
    ]))->assertSessionHasNoErrors();

    expect(Setting::find('ai_backup_api_key')->value)->not->toContain('gsk-rahasia');

    RuntimeConfig::apply();
    expect(config('services.ai.backup'))->toBe(['base_url' => 'https://api.groq.com/openai/v1', 'key' => 'gsk-rahasia-cadangan', 'model' => 'llama-3.3-70b-versatile'])
        ->and(config('services.ai.fast_model'))->toBe('llama-3.1-8b-instant')
        ->and(app(AiService::class)->hasBackup())->toBeTrue();

    $this->get(route('settings.edit'))->assertSee('Provider cadangan')->assertSee('Siap')->assertDontSee('gsk-rahasia-cadangan');

    $this->put(route('settings.connections'), connectionPayload(['ai_backup_base_url' => 'https://x.test/v1']))
        ->assertSessionHasErrors('ai_backup_model');
});

/*
|--------------------------------------------------------------------------
| 9.3 Quality gate
|--------------------------------------------------------------------------
*/

test('the quality gate flags placeholders, clichés, scraped numbers and odd lengths', function () {
    $gate = app(MessageQualityGate::class);
    $lead = reliabilityLead(['rating' => 4.8, 'reviews_count' => 1250, 'address' => 'Jl. Raya Rungkut Kidul No.21, Surabaya']);
    $body = fn (string $extra) => 'Selamat siang, tim Klinik Gigi Sehat. '.$extra.' '.str_repeat('Kami membantu klinik membuat website sederhana untuk pasien. ', 4);

    expect($gate->problems(preg_replace('/^SUBJEK:[^\n]*\n+/', '', GOOD_EMAIL), 'website untuk klinik?', $lead, 'email'))->toBe([])
        ->and($gate->problems($body('Halo [Nama Anda].'), null, $lead, 'email'))->toContain('Masih ada placeholder seperti [Nama] atau {{...}}')
        ->and(implode(' ', $gate->problems($body('Semoga email ini menemukan Anda.'), null, $lead, 'email')))->toContain('semoga email ini')
        ->and($gate->problems($body('Rating 4,8 Anda luar biasa.'), null, $lead, 'email'))->toContain('Menyebut angka rating')
        ->and($gate->problems($body('Ada 1.250 ulasan.'), null, $lead, 'email'))->toContain('Menyebut jumlah ulasan')
        ->and($gate->problems($body('Klinik di Jl. Raya Rungkut Kidul No.21 itu.'), null, $lead, 'email'))->toContain('Menyebut alamat')
        ->and($gate->problems('Halo, boleh kirim contoh?', null, $lead, 'email')[0])->toStartWith('Terlalu pendek');
});

test('a message that fails the gate is rewritten once and flagged if it is still bad', function () {
    fakeAiConfigured();
    $bad = "SUBJEK: halo\n\nHalo [Nama], semoga email ini menemukan Anda dalam keadaan baik.";

    Http::fakeSequence('ai.test/*')
        ->push(aiResponse($bad))->push(aiResponse(GOOD_EMAIL)) // diperbaiki di percobaan kedua
        ->push(aiResponse($bad))->push(aiResponse($bad));     // tetap buruk
    $fixed = app(AiService::class)->generateOutreach(reliabilityLead(), 'email');

    expect($fixed['needs_review'])->toBeFalse()->and($fixed['message'])->toContain('contoh tampilannya');
    Http::assertSent(fn ($r) => str_contains($r['messages'][1]['content'], 'Tulisan sebelumnya ditolak karena: Masih ada placeholder'));

    $flagged = app(AiService::class)->generateOutreach(reliabilityLead(['business_name' => 'Klinik Lain']), 'email');

    expect($flagged['needs_review'])->toBeTrue()->and($flagged['problems'])->not->toBeEmpty();
});

test('flagged messages are stored for review and skipped by bulk sending until edited', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse("SUBJEK: halo\n\nHalo [Nama]."))]);
    $lead = reliabilityLead();
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'klinik', 'location' => 'Surabaya']);

    (new GenerateOutreachJob($lead->id, $campaign->id, 'email', ['mode' => 'ai']))->handle(app(AiService::class));

    $message = OutreachMessage::first();
    expect($message->needs_review)->toBeTrue()->and($message->prompt_variant)->not->toBeNull();

    $this->get(route('outreach.index'))->assertSee('Perlu review');
    expect(app(OutreachSender::class)->queue(collect([$message]))['skipped'])->toBe(1);

    $this->put(route('outreach.update', $message), ['subject' => 'soal website', 'message' => 'Pesan yang sudah saya rapikan sendiri.']);
    expect($message->fresh()->needs_review)->toBeFalse()
        ->and(app(OutreachSender::class)->queue(collect([$message->fresh()]))['queued'])->toBe(1);
});

test('existing drafts can be re-checked against the quality gate', function () {
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'klinik', 'location' => 'Surabaya']);
    $lead = reliabilityLead(['rating' => 4.9, 'reviews_count' => 93]);
    $base = ['lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'email', 'status' => 'pending', 'mode' => 'ai'];
    $bad = OutreachMessage::create($base + ['subject' => 'Peluang peningkatan kehadiran digital', 'message' => 'Rating 4.9 dari 93 ulasan menunjukkan kualitas. '.str_repeat('Kami membantu klinik membuat website sederhana. ', 5)]);
    $good = OutreachMessage::create($base + ['subject' => 'website untuk klinik?', 'message' => preg_replace('/^SUBJEK:[^\n]*\n+/', '', GOOD_EMAIL)]);
    $template = OutreachMessage::create(['mode' => 'template', 'message' => 'Halo [Nama]'] + $base);

    $this->artisan('outreach:check-drafts --dry-run')->expectsOutputToContain('1 akan ditandai')->assertSuccessful();
    expect($bad->fresh()->needs_review)->toBeFalse();

    $this->artisan('outreach:check-drafts')->expectsOutputToContain('1 ditandai')->assertSuccessful();
    expect($bad->fresh()->needs_review)->toBeTrue()
        ->and($good->fresh()->needs_review)->toBeFalse()
        ->and($template->fresh()->needs_review)->toBeFalse(); // template ditulis pengguna sendiri
});

/*
|--------------------------------------------------------------------------
| 9.4 Varian prompt
|--------------------------------------------------------------------------
*/

test('leads are split between two opening styles, or one style when disabled', function () {
    $ai = app(AiService::class);
    $first = reliabilityLead(['business_name' => 'Satu']);
    $second = reliabilityLead(['business_name' => 'Dua']);

    expect($ai->variantFor($first))->not->toBe($ai->variantFor($second));

    $question = $ai->variantFor($first) === 'pertanyaan' ? $first : $second;
    expect($ai->outreachPrompt($question, 'email'))->toContain('satu pertanyaan singkat tentang cara calon pelanggan');

    Setting::put(['ai_prompt_variants' => '0']);
    expect($ai->variantFor($first))->toBe('observasi')->and($ai->variantFor($second))->toBe('observasi')
        ->and($ai->outreachPrompt($question, 'email'))->toContain('satu pengamatan spesifik');
});

test('the dashboard compares reply rates per prompt variant', function () {
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'klinik', 'location' => 'Surabaya']);
    $base = ['campaign_id' => $campaign->id, 'type' => 'email', 'message' => 'x', 'mode' => 'ai', 'sent_at' => now()];
    OutreachMessage::create($base + ['lead_id' => reliabilityLead(['business_name' => 'A'])->id, 'status' => 'replied', 'prompt_variant' => 'pertanyaan']);
    OutreachMessage::create($base + ['lead_id' => reliabilityLead(['business_name' => 'B'])->id, 'status' => 'sent', 'prompt_variant' => 'observasi']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Varian Gaya Pesan AI')
        ->assertSeeInOrder(['Buka dengan pengamatan', '0%', 'Buka dengan pertanyaan', '100%']);
});
