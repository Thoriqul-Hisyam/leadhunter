<?php

use App\Exceptions\ScrapeFailedException;
use App\Jobs\EnrichLeadJob;
use App\Jobs\GenerateOutreachJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Services\AiService;
use App\Services\Scraping\LeadScraperService;
use App\Services\WebsiteAuditService;
use App\Services\WebsiteCrawlerService;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => loginAs());

function qualityLead(array $attributes = []): Lead
{
    return Lead::create($attributes + ['business_name' => 'Lead '.uniqid(), 'niche' => 'klinik', 'city' => 'Surabaya', 'source' => 'manual']);
}

test('lead scores favour businesses that need a website, are popular and reachable', function () {
    $hot = qualityLead(['rating' => 4.8, 'reviews_count' => 500, 'phone' => '0812 3456 7890', 'email' => 'a@b.id']);
    $instagram = qualityLead(['website' => 'https://instagram.com/klinik', 'rating' => 4.6, 'reviews_count' => 80, 'phone' => '0813 1111 2222']);
    $fineWebsite = qualityLead(['website' => 'https://klinik.id', 'rating' => 3.9, 'reviews_count' => 5, 'phone' => '(031) 5964600']);

    expect($hot->score)->toBe(95)->and($hot->isHot())->toBeTrue()
        ->and($instagram->score)->toBe(73)
        ->and($fineWebsite->score)->toBe(10)->and($fineWebsite->isHot())->toBeFalse();

    // Audit menemukan website lambat → kebutuhan naik
    $fineWebsite->update(['website_score' => 32, 'website_https' => true]);
    expect($fineWebsite->fresh()->score)->toBe(25);
});

test('leads can be sorted and filtered by score', function () {
    qualityLead(['business_name' => 'Rendah', 'website' => 'https://x.id']);
    qualityLead(['business_name' => 'Tinggi', 'rating' => 4.9, 'reviews_count' => 300, 'phone' => '0812 3456 7890', 'email' => 'a@b.id']);

    $this->get(route('leads.index', ['sort' => 'score']))->assertSeeInOrder(['Tinggi', 'Rendah']);
    $this->get(route('leads.index', ['min_score' => 70]))->assertSee('Tinggi')->assertDontSee('Rendah')->assertSee('Hot');
});

test('branches with the same name in the same city stay separate leads', function () {
    $service = app(LeadScraperService::class);
    $place = fn (string $id, string $address) => [
        'name' => 'Kopi Kenangan', 'address' => $address,
        'google_maps_url' => "https://www.google.com/maps/place/Kopi+Kenangan/data=!4m7!3m6!1s{$id}!8m2",
    ];

    expect($service->saveLead($place('0xaaa:0x111', 'Jl. A'), 'cafe', 'Surabaya', 'google_maps')[0])->toBe('created')
        ->and($service->saveLead($place('0xbbb:0x222', 'Jl. B'), 'cafe', 'Surabaya', 'google_maps')[0])->toBe('created')
        ->and($service->saveLead($place('0xaaa:0x111', 'Jl. A'), 'cafe', 'Surabaya', 'google_maps')[0])->toBe('unchanged');

    expect(Lead::where('business_name', 'Kopi Kenangan')->count())->toBe(2)
        ->and(Lead::pluck('place_id')->sort()->values()->all())->toBe(['0xaaa:0x111', '0xbbb:0x222']);
});

test('legacy leads without a place id are matched by name and city', function () {
    $legacy = qualityLead(['business_name' => 'Klinik Lama', 'city' => 'Surabaya']);

    [$outcome, $lead] = app(LeadScraperService::class)->saveLead([
        'name' => 'Klinik Lama', 'phone' => '0812 0000 1111',
        'google_maps_url' => 'https://www.google.com/maps/place/x/data=!1s0xccc:0x333!8m2',
    ], 'klinik', 'surabaya', 'google_maps');

    expect($outcome)->toBe('updated')->and($lead->id)->toBe($legacy->id)->and($lead->place_id)->toBe('0xccc:0x333');
});

test('bulk crawl and audit run as a background batch for the selected leads', function () {
    Bus::fake();
    $a = qualityLead(['website' => 'https://a.id']);
    $b = qualityLead(['website' => 'https://b.id', 'email' => 'b@b.id', 'phone' => '0812 3456 7890']);
    qualityLead(); // tanpa website

    $this->post(route('leads.bulk'), ['action' => 'crawl', 'lead_ids' => Lead::pluck('id')->all()])
        ->assertSessionHas('success', fn ($m) => str_contains($m, 'untuk 1 lead'));

    Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === 1
        && $batch->jobs->first()->leadId === $a->id
        && $batch->jobs->first()->action === 'crawl');

    $this->post(route('leads.bulk'), ['action' => 'audit', 'select_all' => 1])->assertSessionHas('success', fn ($m) => str_contains($m, 'untuk 2 lead'));
});

test('the enrich job crawls contacts or stores a website audit', function () {
    $lead = qualityLead(['website' => 'http://93.184.216.34']);

    Process::fake(['*' => Process::result('RESULT_JSON:{"success":true,"email":"halo@klinik.id","phone":null}')]);
    (new EnrichLeadJob($lead->id, 'crawl'))->handle(app(WebsiteCrawlerService::class), app(WebsiteAuditService::class));
    expect($lead->fresh()->email)->toBe('halo@klinik.id');

    Http::fake(['www.googleapis.com/*' => Http::response(['lighthouseResult' => [
        'finalDisplayedUrl' => 'http://93.184.216.34/',
        'categories' => ['performance' => ['score' => 0.34]],
    ]])]);
    (new EnrichLeadJob($lead->id, 'audit'))->handle(app(WebsiteCrawlerService::class), app(WebsiteAuditService::class));

    $lead->refresh();
    expect($lead->website_score)->toBe(34)
        ->and($lead->website_https)->toBeFalse()
        ->and($lead->website_audited_at)->not->toBeNull();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'strategy=mobile'));
});

test('an exhausted pagespeed quota tells the user to add an api key', function () {
    Http::fake(['www.googleapis.com/*' => Http::response(['error' => ['code' => 429, 'message' => "Quota exceeded for quota metric 'Queries' and limit 'Queries per day'"]], 429)]);

    expect(fn () => app(WebsiteAuditService::class)->audit('https://klinik.id'))
        ->toThrow(\App\Exceptions\CrawlException::class, 'Isi Google PageSpeed API key');
});

test('audit findings shape the ai pitch without quoting scores', function () {
    $lead = qualityLead(['website' => 'https://klinik.id', 'website_score' => 28, 'website_https' => false]);

    $prompt = app(AiService::class)->outreachPrompt($lead, 'email');

    expect($prompt)->toContain('terbuka lambat di HP')
        ->toContain('belum memakai HTTPS')
        ->toContain('tanpa angka skor')
        ->not->toContain('28');
});

test('bulk generate from the leads table accepts "select all matching filter"', function () {
    Queue::fake();
    qualityLead(['business_name' => 'Punya Email', 'email' => 'a@a.id']);
    qualityLead(['business_name' => 'Tanpa Email']);
    $campaign = Campaign::create(['name' => 'C', 'niche' => 'klinik', 'location' => 'Surabaya']);

    $this->post(route('outreach.generate'), ['campaign_id' => $campaign->id, 'type' => 'email', 'select_all' => 1, 'has_email' => 'yes'])
        ->assertRedirect(route('campaigns.show', $campaign));

    Queue::assertPushed(GenerateOutreachJob::class, 1);
});

test('the composer lead endpoint pages results and returns ids for "select all"', function () {
    foreach (range(1, 35) as $i) {
        qualityLead(['business_name' => "Klinik {$i}", 'email' => $i % 2 ? "k{$i}@x.id" : null]);
    }

    $this->getJson(route('campaigns.leads.filter', ['per_page' => 30]))
        ->assertOk()
        ->assertJsonCount(30, 'leads')
        ->assertJsonPath('pagination.total', 35)
        ->assertJsonPath('pagination.has_more', true);

    $this->getJson(route('campaigns.leads.filter', ['ids_only' => 1, 'has_email' => 'yes']))
        ->assertOk()
        ->assertJsonCount(18, 'leads')
        ->assertJsonStructure(['leads' => [['id', 'business_name']]]);

    // Halaman Outreach tidak lagi memuat semua lead ke HTML
    $this->get(route('outreach.index'))->assertOk()->assertDontSee('Klinik 35')->assertSee('Memuat lead');
});

test('a google captcha is reported clearly instead of "no results"', function () {
    Process::fake(['*' => Process::result(output: 'SUMMARY:{"found":0,"blocked":"captcha"}', errorOutput: 'BLOCKED:captcha', exitCode: 3)]);

    expect(fn () => app(LeadScraperService::class)->scrape('cafe', 'bali'))
        ->toThrow(ScrapeFailedException::class, 'CAPTCHA');
});
