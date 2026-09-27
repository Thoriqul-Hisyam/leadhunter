<?php

use App\Jobs\GenerateOutreachJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;
use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => loginAs());

function makeLeads(int $count): \Illuminate\Support\Collection
{
    return collect(range(1, $count))->map(fn ($i) => Lead::create([
        'business_name' => "Bisnis {$i}", 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'email' => "b{$i}@cafe.id", 'phone' => '0812000'.$i,
    ]));
}

test('ai generation is queued per lead and channel instead of running in the request', function () {
    Queue::fake();
    $leads = makeLeads(3);

    $this->post(route('campaigns.store'), [
        'name' => 'Cafe Bali', 'niche' => 'cafe', 'location' => 'Bali',
        'lead_ids' => $leads->pluck('id')->all(), 'auto_generate' => 1,
        'outreach_mode' => 'ai_generate', 'channels' => ['email', 'whatsapp'],
    ])->assertRedirect();

    $campaign = Campaign::first();
    Queue::assertPushed(GenerateOutreachJob::class, 6);
    expect($campaign->generation_total)->toBe(6)->and($campaign->isGenerating())->toBeTrue();

    $this->getJson(route('campaigns.progress', $campaign))->assertJson(['total' => 6, 'done' => 0, 'generating' => true]);
});

test('template mode renders messages instantly with sender settings', function () {
    Queue::fake();
    $lead = makeLeads(1)->first();
    $template = MessageTemplate::create([
        'name' => 'T', 'channel' => 'email', 'niche' => 'cafe', 'language' => 'id', 'tone' => 'formal',
        'subject' => 'Untuk {{business_name}}', 'body' => 'Salam dari {{sender_name}} ({{company_website}}) soal {{offer}}.',
    ]);

    $this->post(route('campaigns.store'), [
        'name' => 'C', 'niche' => 'cafe', 'location' => 'Bali', 'lead_ids' => [$lead->id], 'auto_generate' => 1,
        'outreach_mode' => 'template', 'template_id' => $template->id, 'channels' => ['email'],
    ]);

    Queue::assertNothingPushed();
    $message = OutreachMessage::first();
    expect($message->subject)->toBe('Untuk Bisnis 1')
        ->and($message->message)->toBe('Salam dari Thoriq dari Lefateach (lefateach.com) soal Jasa Pembuatan Website Profesional.')
        ->and($message->mode)->toBe('template');
});

test('the generate job creates a pending message and reports completion', function () {
    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse('Yth. Bapak/Ibu, ini pesan AI.'))]);

    $lead = makeLeads(1)->first();
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'cafe', 'location' => 'Bali']);
    $campaign->queueGeneration(1);

    (new GenerateOutreachJob($lead->id, $campaign->id, 'email'))->handle(app(AiService::class));

    expect(OutreachMessage::first()->message)->toBe("Yth. Bapak/Ibu, ini pesan AI.

Salam,
Thoriq
Lefateach · lefateach.com")
        ->and($campaign->fresh()->generation_done)->toBe(1)
        ->and(ScrapingNotification::where('title', 'Generate Pesan Selesai')->exists())->toBeTrue();
});

test('a failed generation is counted without stopping the batch', function () {
    $leads = makeLeads(2);
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'cafe', 'location' => 'Bali']);
    $campaign->queueGeneration(2);

    (new GenerateOutreachJob($leads[0]->id, $campaign->id, 'email'))->failed(new RuntimeException('429'));
    expect($campaign->fresh()->isGenerating())->toBeTrue();

    fakeAiConfigured();
    Http::fake(['ai.test/*' => Http::response(aiResponse('Pesan kedua'))]);
    (new GenerateOutreachJob($leads[1]->id, $campaign->id, 'email'))->handle(app(AiService::class));

    $campaign->refresh();
    expect($campaign->generation_done)->toBe(1)
        ->and($campaign->generation_failed)->toBe(1)
        ->and($campaign->isGenerating())->toBeFalse()
        ->and(OutreachMessage::count())->toBe(1);
});

test('bulk generate from the leads table queues one job per lead', function () {
    Queue::fake();
    $leads = makeLeads(3);
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'cafe', 'location' => 'Bali']);

    $this->post(route('outreach.generate'), ['campaign_id' => $campaign->id, 'lead_ids' => $leads->pluck('id')->all(), 'type' => 'whatsapp'])
        ->assertRedirect(route('campaigns.show', $campaign));

    Queue::assertPushed(GenerateOutreachJob::class, 3);
});

test('unselecting a lead keeps its sent and replied history', function () {
    $leads = makeLeads(2);
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'cafe', 'location' => 'Bali']);

    $base = ['campaign_id' => $campaign->id, 'type' => 'email', 'message' => 'x'];
    $sent = OutreachMessage::create($base + ['lead_id' => $leads[0]->id, 'status' => 'sent', 'sent_at' => now()]);
    $replied = OutreachMessage::create($base + ['lead_id' => $leads[0]->id, 'status' => 'replied', 'type' => 'whatsapp']);
    $draft = OutreachMessage::create($base + ['lead_id' => $leads[1]->id, 'status' => 'pending']);

    $this->put(route('campaigns.update', $campaign), ['name' => 'C', 'niche' => 'cafe', 'location' => 'Bali', 'lead_ids' => []])
        ->assertSessionHas('success', fn ($msg) => str_contains($msg, '1 lead tetap tercatat'));

    expect(OutreachMessage::find($sent->id))->not->toBeNull()
        ->and(OutreachMessage::find($replied->id))->not->toBeNull()
        ->and(OutreachMessage::find($draft->id))->toBeNull();
});
