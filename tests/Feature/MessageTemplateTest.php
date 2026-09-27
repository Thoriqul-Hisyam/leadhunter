<?php

use App\Models\MessageTemplate;
use App\Models\Lead;

beforeEach(fn () => loginAs());

test('user can view templates list', function () {
    MessageTemplate::create([
        'name' => 'Dentist Email Template',
        'channel' => 'email',
        'niche' => 'dentist',
        'language' => 'id',
        'tone' => 'formal',
        'subject' => 'Kerjasama Digital Marketing Klinik Gigi',
        'body' => 'Halo {{ business_name }}, kami menawarkan {{ offer }} untuk klinik Anda di {{ city }}. Hubungi kami di {{ phone }}.',
        'is_active' => true,
    ]);

    $response = $this->get(route('templates.index'));
    $response->assertStatus(200);
    $response->assertSee('Dentist Email Template');
});

test('user can create a message template', function () {
    $response = $this->post(route('templates.store'), [
        'name' => 'New Test Template',
        'channel' => 'whatsapp',
        'niche' => 'cafe',
        'language' => 'id',
        'tone' => 'casual',
        'body' => 'Halo {{ business_name }}, ada penawaran menarik.',
        'is_active' => 1,
    ]);

    $response->assertRedirect(route('templates.index'));
    $this->assertDatabaseHas('message_templates', [
        'name' => 'New Test Template',
        'channel' => 'whatsapp',
    ]);
});

test('it renders variables correctly', function () {
    $lead = Lead::create([
        'business_name' => 'Klinik Gigi Sehat',
        'city' => 'Surabaya',
        'niche' => 'dentist',
        'website' => 'https://klinikgigisehat.com',
        'phone' => '08123456789',
        'email' => 'info@klinikgigisehat.com',
        'address' => 'Jl. Dharmawangsa No. 12',
        'source' => 'manual',
    ]);

    $templateText = 'Halo {{ business_name }}, kami menawarkan {{ offer }} di {{ city }}. Hubungi {{ phone }} atau kunjungi {{ website }}. Terimakasih, {{ sender_name }}';
    
    $renderedText = MessageTemplate::render($templateText, $lead, [
        'offer' => 'Jasa Pembuatan Website',
        'sender_name' => 'Thoriq dari Lefateach',
    ]);

    expect($renderedText)->toBe('Halo Klinik Gigi Sehat, kami menawarkan Jasa Pembuatan Website di Surabaya. Hubungi 08123456789 atau kunjungi https://klinikgigisehat.com. Terimakasih, Thoriq dari Lefateach');
});

test('placeholder chips insert double-brace tags that the renderer understands', function () {
    $html = $this->get(route('templates.create'))->assertOk()->getContent();

    expect($html)->toContain("injectPlaceholder('{{business_name}}')")
        ->toContain("injectPlaceholder('{{company_website}}')")
        ->not->toContain("injectPlaceholder('{business_name}')");
});
