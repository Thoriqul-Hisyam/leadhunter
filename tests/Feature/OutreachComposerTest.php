<?php

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\OutreachMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it filters active templates by niche and channel', function () {
    MessageTemplate::create([
        'name' => 'Dentist Email Template',
        'channel' => 'email',
        'niche' => 'dentist',
        'language' => 'id',
        'tone' => 'formal',
        'body' => 'Dentist Body {{ business_name }}',
        'is_active' => true,
    ]);

    MessageTemplate::create([
        'name' => 'Cafe WA Template',
        'channel' => 'whatsapp',
        'niche' => 'cafe',
        'language' => 'id',
        'tone' => 'casual',
        'body' => 'Cafe Body {{ business_name }}',
        'is_active' => true,
    ]);

    // Test filter by email
    $response = $this->getJson(route('outreach.templates.filter', ['channel' => 'email']));
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'templates');
    $response->assertJsonPath('templates.0.name', 'Dentist Email Template');

    // Test filter by whatsapp
    $response = $this->getJson(route('outreach.templates.filter', ['channel' => 'whatsapp']));
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'templates');
    $response->assertJsonPath('templates.0.name', 'Cafe WA Template');
});

test('it previews templates outreach messages successfully', function () {
    $lead = Lead::create([
        'business_name' => 'Dr. Gigi Dental',
        'city' => 'Jakarta',
        'niche' => 'dentist',
        'phone' => '081111111',
        'email' => 'gigi@dental.com',
        'address' => 'Jakarta City',
        'source' => 'manual',
    ]);

    $template = MessageTemplate::create([
        'name' => 'Dentist Email Template',
        'channel' => 'email',
        'niche' => 'dentist',
        'language' => 'id',
        'tone' => 'formal',
        'subject' => 'Kerjasama {{ business_name }}',
        'body' => 'Halo {{ business_name }}, kami menawarkan {{ offer }} di {{ city }}. Hubungi kami di {{ phone }}.',
        'is_active' => true,
    ]);

    $response = $this->postJson(route('outreach.compose.preview'), [
        'lead_ids' => [$lead->id],
        'mode' => 'template',
        'template_id' => $template->id,
        'type' => 'email',
        'offer' => 'Jasa Desain Website',
        'sender_name' => 'Ahmad dari Lefateach',
    ]);

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'previews');
    $response->assertJsonPath('previews.0.subject', 'Kerjasama Dr. Gigi Dental');
    $response->assertJsonPath('previews.0.message', 'Halo Dr. Gigi Dental, kami menawarkan Jasa Desain Website di Jakarta. Hubungi kami di 081111111.');
});

test('it saves composed outreach messages to the database pipeline', function () {
    $campaign = Campaign::create([
        'name' => 'Jakarta Dental Campaign',
        'niche' => 'dentist',
    ]);

    $lead = Lead::create([
        'business_name' => 'Dr. Gigi Dental',
        'city' => 'Jakarta',
        'niche' => 'dentist',
        'phone' => '081111111',
        'email' => 'gigi@dental.com',
        'address' => 'Jakarta City',
        'source' => 'manual',
    ]);

    $template = MessageTemplate::create([
        'name' => 'Dentist Email Template',
        'channel' => 'email',
        'niche' => 'dentist',
        'language' => 'id',
        'tone' => 'formal',
        'subject' => 'Subject',
        'body' => 'Body',
        'is_active' => true,
    ]);

    $response = $this->postJson(route('outreach.compose.save'), [
        'campaign_id' => $campaign->id,
        'type' => 'email',
        'mode' => 'template',
        'template_id' => $template->id,
        'messages' => [
            [
                'lead_id' => $lead->id,
                'subject' => 'Custom Subject for Dr. Gigi Dental',
                'message' => 'Custom message body here',
            ]
        ]
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');

    $this->assertDatabaseHas('outreach_messages', [
        'campaign_id' => $campaign->id,
        'lead_id' => $lead->id,
        'type' => 'email',
        'subject' => 'Custom Subject for Dr. Gigi Dental',
        'message' => 'Custom message body here',
        'status' => 'pending',
        'mode' => 'template',
        'template_id' => $template->id,
    ]);
});
