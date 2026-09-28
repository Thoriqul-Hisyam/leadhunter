<?php

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use App\Services\AiService;
use App\Services\RuntimeConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => loginAs('admin'));

function connectionPayload(array $overrides = []): array
{
    return $overrides + [
        'ai_base_url' => 'https://router.test/v1',
        'ai_api_key' => 'sk-rahasia-123',
        'ai_model' => 'model-x',
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.gmail.com',
        'mail_port' => 587,
        'mail_username' => 'saya@gmail.com',
        'mail_password' => 'abcd efgh ijkl mnop',
        'mail_from_address' => 'saya@gmail.com',
        'mail_from_name' => 'Thoriq · Lefateach',
        'imap_enabled' => '1',
    ];
}

test('ai and mail connections are stored in the database with secrets encrypted', function () {
    $this->put(route('settings.connections'), connectionPayload())->assertRedirect();

    expect(Setting::find('ai_api_key')->value)->not->toContain('sk-rahasia')
        ->and(Setting::get('ai_api_key'))->toBe('sk-rahasia-123')
        ->and(Setting::get('mail_password'))->toBe('abcdefghijklmnop') // spasi App Password dibuang
        ->and(Setting::find('mail_password')->value)->not->toContain('abcd');

    RuntimeConfig::apply();

    expect(config('services.ai.base_url'))->toBe('https://router.test/v1')
        ->and(config('services.ai.key'))->toBe('sk-rahasia-123')
        ->and(config('services.ai.model'))->toBe('model-x')
        ->and(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.gmail.com')
        ->and(config('mail.mailers.smtp.port'))->toBe(587)
        ->and(config('mail.mailers.smtp.username'))->toBe('saya@gmail.com')
        ->and(config('mail.mailers.smtp.password'))->toBe('abcdefghijklmnop')
        ->and(config('mail.from.address'))->toBe('saya@gmail.com')
        ->and(config('leadhunter.imap.enabled'))->toBeTrue()
        ->and(config('leadhunter.imap.username'))->toBe('saya@gmail.com');
});

test('blank secret fields keep the stored value and the clear checkbox removes it', function () {
    $this->put(route('settings.connections'), connectionPayload());

    $this->put(route('settings.connections'), connectionPayload(['ai_api_key' => '', 'mail_password' => '']));
    expect(Setting::get('ai_api_key'))->toBe('sk-rahasia-123')
        ->and(Setting::get('mail_password'))->toBe('abcdefghijklmnop');

    $this->put(route('settings.connections'), connectionPayload(['ai_api_key' => '', 'clear_ai_api_key' => '1']));
    expect(Setting::get('ai_api_key'))->toBe('');
});

test('the settings page never renders stored secrets', function () {
    $this->put(route('settings.connections'), connectionPayload());
    RuntimeConfig::apply();

    $this->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('value="https://router.test/v1"', false)
        ->assertSee('(tersimpan, isi untuk mengganti)')
        ->assertDontSee('sk-rahasia-123')
        ->assertDontSee('abcdefghijklmnop');
});

test('the pagespeed api key is stored encrypted and used by the website audit', function () {
    $this->put(route('settings.connections'), connectionPayload(['pagespeed_api_key' => 'AIza-rahasia']));

    expect(Setting::find('pagespeed_api_key')->value)->not->toContain('AIza')
        ->and(Setting::get('pagespeed_api_key'))->toBe('AIza-rahasia');

    RuntimeConfig::apply();
    expect(config('services.pagespeed.key'))->toBe('AIza-rahasia');

    $this->get(route('settings.edit'))->assertSee('Hapus API key tersimpan')->assertDontSee('AIza-rahasia');

    $this->put(route('settings.connections'), connectionPayload(['clear_pagespeed_api_key' => '1']));
    expect(Setting::get('pagespeed_api_key'))->toBe('');
});

test('smtp mode requires host, port, username and sender address', function () {
    $this->put(route('settings.connections'), ['mail_mailer' => 'smtp'])
        ->assertSessionHasErrors(['mail_host', 'mail_port', 'mail_username', 'mail_from_address']);

    $this->put(route('settings.connections'), ['mail_mailer' => 'log'])->assertSessionHasNoErrors();
});

test('the ai connection can be tested from the settings page', function () {
    $this->put(route('settings.connections'), connectionPayload(['ai_base_url' => 'https://ai.test/v1']));
    RuntimeConfig::apply();
    Http::fake(['ai.test/*' => Http::response(aiResponse('OK'))]);

    $this->post(route('settings.test-ai'))->assertSessionHas('success', fn ($m) => str_contains($m, 'AI terhubung') && str_contains($m, '"OK"'));

    Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sk-rahasia-123'));
});

test('a test email can be sent from the settings page', function () {
    Mail::fake();
    config(['mail.default' => 'smtp', 'mail.from.address' => 'saya@gmail.com']);

    $this->post(route('settings.test-mail'))->assertSessionHas('success', fn ($m) => str_contains($m, 'saya@gmail.com'));
});

test('https app urls force https links without crashing on boot', function () {
    config(['app.url' => 'https://leadhunter.example.com']);

    (new AppServiceProvider(app()))->boot();

    expect(url('/x'))->toStartWith('https://');
});

test('ai templates can be generated with normalised placeholders and a signature', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse("SUBJEK: website untuk {{ Business_Name }}?\n\nSelamat siang, tim {{business_name}} di {{ city }}.\nBoleh saya kirim contoh?\n\nSalam,\nThoriq"))]);

    $this->postJson(route('templates.generate'), ['channel' => 'email', 'niche' => 'klinik gigi', 'language' => 'id', 'tone' => 'formal'])
        ->assertOk()
        ->assertJsonPath('subject', 'website untuk {{business_name}}?')
        ->assertJsonPath('body', "Selamat siang, tim {{business_name}} di {{city}}.\nBoleh saya kirim contoh?\n\nSalam,\n{{sender_name}}");

    Http::assertSent(fn ($r) => str_contains($r['messages'][1]['content'], 'bidang "klinik gigi"') && str_contains($r['messages'][1]['content'], '{{business_name}}'));
});

test('template generation reports missing niche and AI errors', function () {
    $this->postJson(route('templates.generate'), ['channel' => 'email', 'niche' => '', 'language' => 'id', 'tone' => 'formal'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('niche');

    $this->postJson(route('templates.generate'), ['channel' => 'whatsapp', 'niche' => 'cafe', 'language' => 'id', 'tone' => 'casual'])
        ->assertStatus(500)
        ->assertJsonPath('status', 'error');
});
