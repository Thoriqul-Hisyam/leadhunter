<?php

use App\Exceptions\ScrapeFailedException;
use App\Jobs\ScrapeGoogleMapsJob;
use App\Models\Lead;
use App\Models\ScrapingNotification;
use App\Services\Scraping\LeadScraperService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

function leadRow(array $data): string
{
    return 'LEAD_ROW:'.json_encode($data + [
        'address' => null, 'phone' => null, 'website' => null, 'email' => null,
        'category' => null, 'rating' => null, 'reviews_count' => null, 'google_maps_url' => null,
    ]);
}

test('starting a scrape dispatches the job on the scraping queue', function () {
    loginAs();
    Queue::fake();

    $this->post(route('leads.scrape'), ['niche' => 'klinik gigi', 'location' => 'surabaya'])
        ->assertRedirect(route('leads.index'));

    Queue::assertPushedOn('scraping', ScrapeGoogleMapsJob::class, fn ($job) => $job->niche === 'klinik gigi' && $job->location === 'surabaya');
    expect(ScrapingNotification::where('type', 'running')->count())->toBe(1);
});

test('the scrape job stores leads with rating, category and maps url', function () {
    Process::fake([
        '*' => Process::result(implode("\n", [
            leadRow(['name' => 'Klinik Gigi Sehat', 'address' => 'Jl. Darmo 1', 'phone' => '031-123', 'website' => 'klinikgigi.id', 'category' => 'Dokter gigi', 'rating' => 4.7, 'reviews_count' => 120, 'google_maps_url' => 'https://www.google.com/maps/place/x']),
            leadRow(['name' => 'Dental Care', 'email' => 'INFO@DENTAL.CO.ID']),
            'SUMMARY:{"found":2}',
        ])),
    ]);

    (new ScrapeGoogleMapsJob('klinik gigi', 'surabaya'))->handle(app(LeadScraperService::class));

    $lead = Lead::where('business_name', 'Klinik Gigi Sehat')->first();
    expect($lead)->not->toBeNull()
        ->and($lead->city)->toBe('Surabaya')
        ->and($lead->rating)->toBe(4.7)
        ->and($lead->reviews_count)->toBe(120)
        ->and($lead->category)->toBe('Dokter gigi')
        ->and($lead->website)->toBe('https://klinikgigi.id')
        ->and($lead->source)->toBe('google_maps');

    expect(Lead::where('business_name', 'Dental Care')->value('email'))->toBe('info@dental.co.id');

    $notification = ScrapingNotification::where('type', 'success')->first();
    expect($notification->message)->toContain('2 bisnis ditemukan, 2 lead baru');

    Process::assertRan(fn ($process) => str_contains(implode(' ', (array) $process->command), 'scrape-gmaps.js'));
});

test('re-scraping an existing lead only fills empty fields', function () {
    Lead::create(['business_name' => 'Kopi Senja', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'phone' => '0811']);

    Process::fake(['*' => Process::result(leadRow(['name' => 'Kopi Senja', 'phone' => '0999', 'website' => 'https://kopisenja.id', 'rating' => 4.2]))]);

    $stats = app(LeadScraperService::class)->scrape('cafe', 'bali');

    $lead = Lead::first();
    expect($stats)->toMatchArray(['found' => 1, 'created' => 0, 'updated' => 1])
        ->and(Lead::count())->toBe(1)
        ->and($lead->phone)->toBe('0811')
        ->and($lead->website)->toBe('https://kopisenja.id')
        ->and($lead->rating)->toBe(4.2);
});

test('a failed puppeteer run raises an error instead of inventing businesses with AI', function () {
    fakeAiConfigured();
    Http::fake();
    Process::fake([
        '*' => Process::result(output: leadRow(['name' => 'Sempat Tersimpan']), errorOutput: 'Error: net::ERR_CONNECTION_REFUSED', exitCode: 1),
    ]);

    try {
        app(LeadScraperService::class)->scrape('cafe', 'bali');
        $this->fail('Exception expected');
    } catch (ScrapeFailedException $e) {
        expect($e->getMessage())->toContain('ERR_CONNECTION_REFUSED')
            ->and($e->stats['created'])->toBe(1);
    }

    Http::assertNothingSent();
    expect(Lead::pluck('business_name')->all())->toBe(['Sempat Tersimpan']);
});

test('a failed scrape job creates a failed notification', function () {
    $job = new ScrapeGoogleMapsJob('cafe', 'bali');
    $job->failed((new ScrapeFailedException('Puppeteer gagal: timeout'))->withStats(['created' => 3]));

    $notification = ScrapingNotification::where('type', 'failed')->first();
    expect($notification->message)->toContain('gagal: Puppeteer gagal: timeout')->toContain('3 lead baru sempat tersimpan');
});

test('the google places driver maps api results', function () {
    config(['leadhunter.scraper.driver' => 'google_places', 'services.google_places.key' => 'places-key']);
    Process::fake(); // crawl email website

    Http::fake([
        'places.googleapis.com/*' => Http::response(['places' => [[
            'displayName' => ['text' => 'Cafe Kita'],
            'formattedAddress' => 'Jl. Sunset 5, Bali',
            'nationalPhoneNumber' => '0361 123',
            'rating' => 4.5,
            'userRatingCount' => 88,
            'googleMapsUri' => 'https://maps.google.com/?cid=1',
            'primaryTypeDisplayName' => ['text' => 'Kafe'],
        ]]]),
    ]);

    $stats = app(LeadScraperService::class)->scrape('cafe', 'bali');

    expect($stats['created'])->toBe(1);
    $lead = Lead::first();
    expect($lead->business_name)->toBe('Cafe Kita')
        ->and($lead->source)->toBe('google_places')
        ->and($lead->category)->toBe('Kafe')
        ->and($lead->reviews_count)->toBe(88);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Goog-Api-Key', 'places-key') && $request['textQuery'] === 'cafe bali');
});

test('the apify driver maps dataset items', function () {
    config(['leadhunter.scraper.driver' => 'apify', 'services.apify.token' => 'apify-token']);

    Http::fake([
        'api.apify.com/*' => Http::response([[
            'title' => 'Barbershop Oke', 'address' => 'Jl. A', 'phone' => '0812', 'website' => null,
            'categoryName' => 'Barber', 'totalScore' => 4.9, 'reviewsCount' => 10, 'url' => 'https://maps/x', 'emails' => ['oke@barber.id'],
        ]]),
    ]);

    app(LeadScraperService::class)->scrape('barbershop', 'jakarta');

    $lead = Lead::first();
    expect($lead->business_name)->toBe('Barbershop Oke')
        ->and($lead->email)->toBe('oke@barber.id')
        ->and($lead->source)->toBe('apify');
});
