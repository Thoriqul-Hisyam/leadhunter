<?php

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;

beforeEach(fn () => loginAs());

test('leads can be filtered by contact availability and stage', function () {
    Lead::create(['business_name' => 'Punya Email', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'email' => 'a@a.id']);
    Lead::create(['business_name' => 'Tanpa Email', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'website' => 'https://x.id', 'pipeline_stage' => 'meeting']);

    $this->get(route('leads.index', ['has_email' => 'yes']))->assertSee('Punya Email')->assertDontSee('Tanpa Email');
    $this->get(route('leads.index', ['has_website' => 'no']))->assertSee('Punya Email')->assertDontSee('Tanpa Email');
    $this->get(route('leads.index', ['stage' => 'meeting']))->assertSee('Tanpa Email')->assertDontSee('Punya Email');
});

test('leads export to csv honours filters', function () {
    Lead::create(['business_name' => 'Cafe A', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'email' => 'a@a.id', 'rating' => 4.5]);
    Lead::create(['business_name' => 'Cafe B', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual']);

    $response = $this->get(route('leads.export', ['has_email' => 'yes']));

    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    expect($csv)->toContain('business_name,niche,category,city')
        ->toContain('"Cafe A",cafe,,Bali')
        ->not->toContain('Cafe B');
});

test('outreach results export to csv', function () {
    $lead = Lead::create(['business_name' => 'Cafe A', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual']);
    $campaign = Campaign::create(['name' => 'Bali Q3', 'niche' => 'cafe', 'location' => 'Bali']);
    OutreachMessage::create(['lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'email', 'message' => 'Halo', 'status' => 'replied']);

    $csv = $this->get(route('outreach.export'))->assertOk()->streamedContent();

    expect($csv)->toContain('"Bali Q3","Cafe A"')->toContain('replied');
});

test('leads import from a semicolon csv with indonesian headers and dedups', function () {
    Lead::create(['business_name' => 'Kopi Senja', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual']);

    $csv = "\xEF\xBB\xBFnama;kota;telepon;email;website\n"
        ."Kopi Senja;bali;0811;senja@kopi.id;\n"
        ."Kopi Pagi;Bali;0812;;kopipagi.id\n"
        .";Bali;;;\n";

    $this->post(route('leads.import'), [
        'file' => UploadedFile::fake()->createWithContent('leads.csv', $csv),
        'default_niche' => 'cafe',
    ])->assertSessionHas('success', 'Import selesai: 1 lead baru, 1 diperbarui, 0 sudah ada, 1 baris dilewati.');

    expect(Lead::count())->toBe(2)
        ->and(Lead::where('business_name', 'Kopi Senja')->value('email'))->toBe('senja@kopi.id')
        ->and(Lead::where('business_name', 'Kopi Pagi')->first()->only(['website', 'source', 'niche']))
        ->toBe(['website' => 'https://kopipagi.id', 'source' => 'csv_import', 'niche' => 'cafe']);
});

test('the lead page shows history and supports stage changes and notes', function () {
    $lead = Lead::create(['business_name' => 'Klinik Sehat', 'niche' => 'dentist', 'city' => 'Surabaya', 'source' => 'manual']);

    $this->get(route('leads.show', $lead))->assertOk()->assertSee('Klinik Sehat')->assertSee('Belum punya website');

    $this->post(route('leads.stage', $lead), ['pipeline_stage' => 'meeting'])->assertRedirect();
    expect($lead->fresh()->pipeline_stage)->toBe('meeting');

    $this->post(route('leads.notes.store', $lead), ['body' => 'Meeting Kamis 10:00'])->assertRedirect(route('leads.show', $lead));
    $note = $lead->notes()->first();
    expect($note->body)->toBe('Meeting Kamis 10:00');

    $this->get(route('leads.show', $lead))->assertSee('Meeting Kamis 10:00');

    $this->delete(route('leads.notes.destroy', [$lead, $note]))->assertRedirect();
    expect($lead->notes()->count())->toBe(0);
});

test('automatic stage changes never move a lead backwards', function () {
    $lead = Lead::create(['business_name' => 'X', 'niche' => 'x', 'city' => 'Y', 'source' => 'manual', 'pipeline_stage' => 'meeting']);

    $lead->advanceStage('contacted');
    expect($lead->fresh()->pipeline_stage)->toBe('meeting');

    $lead->advanceStage('deal');
    expect($lead->fresh()->pipeline_stage)->toBe('deal');
});

test('the pipeline board groups leads by stage', function () {
    Lead::create(['business_name' => 'Lead Baru', 'niche' => 'x', 'city' => 'Y', 'source' => 'manual']);
    Lead::create(['business_name' => 'Lead Deal', 'niche' => 'x', 'city' => 'Y', 'source' => 'manual', 'pipeline_stage' => 'deal']);

    $this->get(route('pipeline.index'))->assertOk()->assertSeeInOrder(['Lead Baru', 'Lead Deal']);
});

test('settings drive the sender identity used everywhere', function () {
    $this->put(route('settings.update'), [
        'sender_name' => 'Budi', 'company_name' => 'Webku', 'company_website' => 'webku.id',
        'default_offer' => 'Jasa SEO', 'followup_days' => 5, 'followup_enabled' => '1',
    ])->assertRedirect(route('settings.edit'));

    expect(Setting::senderIdentity())->toBe('Budi dari Webku')
        ->and(Setting::defaultOffer())->toBe('Jasa SEO')
        ->and(Setting::followupEnabled())->toBeTrue()
        ->and(Setting::followupDays())->toBe(5);

    $this->get(route('outreach.index'))->assertSee('value="Budi dari Webku"', false)->assertSee('value="Jasa SEO"', false);
});

test('the blacklist can be managed from settings', function () {
    $this->post(route('blacklist.store'), ['type' => 'phone', 'value' => '0812-3456-789'])->assertRedirect();
    $this->post(route('blacklist.store'), ['type' => 'email', 'value' => 'bukan-email'])->assertSessionHasErrors('value');

    $entry = \App\Models\BlacklistEntry::first();
    expect($entry->value)->toBe('628123456789');

    $this->delete(route('blacklist.destroy', $entry))->assertRedirect();
    expect(\App\Models\BlacklistEntry::count())->toBe(0);
});

test('the dashboard computes real rates instead of mock numbers', function () {
    $lead = Lead::create(['business_name' => 'X', 'niche' => 'x', 'city' => 'Y', 'source' => 'manual']);
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'x', 'location' => 'Y']);
    $make = fn ($status, $extra = []) => OutreachMessage::create($extra + ['lead_id' => $lead->id, 'campaign_id' => $campaign->id, 'type' => 'email', 'message' => 'm', 'status' => $status]);

    $make('sent', ['sent_at' => now()]);
    $make('sent', ['sent_at' => now()]);
    $make('sent', ['sent_at' => now()]);
    $make('replied', ['sent_at' => now(), 'replied_at' => now()]);
    $make('failed');
    $make('pending');

    $response = $this->get(route('dashboard'))->assertOk();
    $stats = $response->viewData('stats');
    $chart = $response->viewData('chart');

    expect($stats['delivered'])->toBe(4)
        ->and($stats['sent_rate'])->toBe(66.7)   // 4 terkirim dari 6 pesan
        ->and($stats['reply_rate'])->toBe(25.0)  // 1 balasan dari 4 terkirim
        ->and($stats['failed_rate'])->toBe(20.0) // 1 gagal dari 5 percobaan
        ->and($chart['labels'])->toHaveCount(30)
        ->and(end($chart['sent']))->toBe(4)
        ->and(end($chart['replies']))->toBe(1);

    $response->assertDontSee('92%')->assertDontSee('85.2');
});
