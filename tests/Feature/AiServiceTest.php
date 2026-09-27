<?php

use App\Exceptions\AiException;
use App\Models\Lead;
use App\Models\Setting;
use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

function aiLead(array $attributes = []): Lead
{
    return Lead::create($attributes + ['business_name' => 'Klinik Gigi Sehat', 'niche' => 'klinik gigi', 'city' => 'Surabaya', 'source' => 'manual']);
}

test('it throws a clear error instead of returning dummy text when no api key is set', function () {
    Http::fake();

    expect(fn () => app(AiService::class)->generateText('halo'))
        ->toThrow(AiException::class, 'AI_API_KEY');

    Http::assertNothingSent();
});

test('it calls the configured openai-compatible endpoint with stream disabled', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse('Halo!'))]);

    expect(app(AiService::class)->generateText('tes'))->toBe('Halo!');

    Http::assertSent(fn ($request) => $request->url() === 'https://ai.test/v1/chat/completions'
        && $request['model'] === 'test-model'
        && $request['stream'] === false
        && $request->hasHeader('Authorization', 'Bearer test-key'));
});

test('it retries on rate limits', function () {
    fakeAiConfigured();
    config(['services.ai.retries' => 2]);
    Sleep::fake();

    Http::fake(['ai.test/*' => Http::sequence()
        ->push(['error' => 'rate limited'], 429)
        ->push(aiResponse('Berhasil'))]);

    expect(app(AiService::class)->generateText('tes'))->toBe('Berhasil');
    Http::assertSentCount(2);
});

test('it parses server-sent events when a router streams anyway', function () {
    fakeAiConfigured();
    $body = "data: {\"choices\":[{\"delta\":{\"content\":\"Halo \"}}]}\n\ndata: {\"choices\":[{\"delta\":{\"content\":\"dunia\"}}]}\n\ndata: [DONE]\n";
    Http::fake(['ai.test/*' => Http::response($body, 200, ['Content-Type' => 'text/event-stream'])]);

    expect(app(AiService::class)->generateText('tes'))->toBe('Halo dunia');
});

test('it reports empty and failed responses as errors', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::sequence()->push(aiResponse(''))->push(['error' => 'boom'], 500)]);

    $ai = app(AiService::class);
    expect(fn () => $ai->generateText('a'))->toThrow(AiException::class, 'kosong');
    expect(fn () => $ai->generateText('b'))->toThrow(AiException::class, '500');
});

test('outreach prompts describe the lead qualitatively instead of dumping raw data', function () {
    Setting::put(['sender_name' => 'Budi', 'company_name' => 'Webku', 'company_website' => 'webku.id', 'default_offer' => 'Jasa SEO']);

    $prompt = app(AiService::class)->outreachPrompt(aiLead([
        'business_name' => 'Klinik Gigi Sehat | Dokter Gigi Surabaya Timur',
        'address' => 'Jl. Raya Rungkut Kidul No.21',
        'rating' => 4.8,
        'reviews_count' => 2781,
    ]), 'email');

    expect($prompt)
        ->toContain('kepada Klinik Gigi Sehat,')          // nama tanpa embel-embel SEO
        ->toContain('sangat populer, banyak ulasan positif')
        ->toContain('Website: belum punya')
        ->toContain('dari Budi dari Webku')
        ->toContain('Yang ditawarkan: Jasa SEO')
        ->toContain('SUBJEK:')
        ->not->toContain('4.8')
        ->not->toContain('2781')
        ->not->toContain('Rungkut Kidul')
        ->not->toContain('Thoriq');
});

test('the pitch angle follows the lead website situation', function () {
    $ai = app(AiService::class);

    expect($ai->outreachPrompt(aiLead(), 'email'))->toContain('belum punya website')
        ->and($ai->outreachPrompt(aiLead(['business_name' => 'B', 'website' => 'https://instagram.com/klinik']), 'email'))->toContain('memakai Instagram sebagai pengganti website')
        ->and($ai->outreachPrompt(aiLead(['business_name' => 'C', 'website' => 'https://klinik.id']), 'email'))->toContain('Mereka sudah punya website. Jangan menyiratkan')
        ->and($ai->outreachPrompt(aiLead(['business_name' => 'D', 'website' => 'https://instagram.com/d']), 'whatsapp'))->not->toContain('SUBJEK:');
});

test('generated messages get a parsed subject, lose AI preambles and signatures, and get our signature', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse("Berikut adalah email untuk Anda:

SUBJEK: website untuk klinik gigi sehat?

Selamat siang, tim **Klinik Gigi Sehat**.
Isi pesan.

Salam hangat,
Thoriq dari Lefateach"))]);

    $result = app(AiService::class)->generateOutreach(aiLead(), 'email');

    expect($result['subject'])->toBe('website untuk klinik gigi sehat?')
        ->and($result['message'])->toBe("Selamat siang, tim Klinik Gigi Sehat.
Isi pesan.

Salam,
Thoriq
Lefateach · lefateach.com");
});

test('whatsapp messages get a short signature and a custom sender replaces it', function () {
    $ai = app(AiService::class);

    expect($ai->parseOutreach("Halo Kak, boleh saya kirim contoh?

Thoriq", aiLead(), 'whatsapp')['message'])
        ->toBe("Halo Kak, boleh saya kirim contoh?

Thoriq · Lefateach")
        ->and($ai->parseOutreach('Halo Kak.', aiLead(['business_name' => 'Klinik Lain']), 'email', ['sender' => 'Ahmad dari Webku'])['message'])
        ->toBe("Halo Kak.

Salam,
Ahmad dari Webku");
});

test('lead display names drop google maps seo suffixes', function () {
    expect(aiLead(['business_name' => 'Moonchelle Beauty Clinic | Klinik Kecantikan Surabaya'])->displayName())->toBe('Moonchelle Beauty Clinic')
        ->and(aiLead(['business_name' => 'Teeth Care Gresik (drg. Yanti)'])->displayName())->toBe('Teeth Care Gresik')
        ->and(aiLead(['business_name' => 'Lumi Dental Driyorejo - Praktek Dokter Gigi'])->displayName())->toBe('Lumi Dental Driyorejo')
        ->and(aiLead(['business_name' => 'Kopi-Kopian'])->displayName())->toBe('Kopi-Kopian');
});

test('smart matching only returns candidate ids', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse('```json {"ids":[1, 2, 999]} ```'))]);

    $ids = app(AiService::class)->matchLeadIds('cafe', 'bali', [
        ['id' => 1, 'name' => 'A', 'niche' => 'cafe', 'city' => 'Bali'],
        ['id' => 2, 'name' => 'B', 'niche' => 'cafe', 'city' => 'Bali'],
    ]);

    expect($ids)->toBe([1, 2]);
});

test('meta notes leaked by an ai router are stripped from messages', function () {
    $clean = app(AiService::class)->cleanMessage("Selamat siang, tim Klinik.\n\nBoleh saya kirim contoh?\n\n→ skipped: [analytics, booking system], add when [client request].\nNote: jangan lupa follow up");

    expect($clean)->toBe("Selamat siang, tim Klinik.\n\nBoleh saya kirim contoh?");
});
