<?php

use App\Models\Lead;
use App\Models\ScrapingNotification;
use App\Models\User;
use Illuminate\Support\Facades\Process;

test('login is throttled after five failed attempts', function () {
    User::factory()->create(['email' => 'user@example.com']);

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong'])->assertRedirect();
    }

    $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong'])->assertStatus(429);
});

test('standard users cannot open admin pages or settings', function () {
    loginAs('user');

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('roles.index'))->assertForbidden();
    $this->get(route('settings.edit'))->assertForbidden();
    $this->get(route('leads.index'))->assertOk();
});

test('users without a role cannot access lead, campaign or outreach pages', function () {
    loginAs(null);

    $this->get(route('leads.index'))->assertForbidden();
    $this->get(route('campaigns.index'))->assertForbidden();
    $this->get(route('outreach.index'))->assertForbidden();
    $this->get(route('templates.index'))->assertForbidden();
    $this->get(route('dashboard'))->assertOk();
});

test('admins can open every management page', function () {
    loginAs('admin');

    foreach (['users.index', 'roles.index', 'settings.edit', 'leads.index', 'pipeline.index', 'campaigns.index', 'outreach.index', 'templates.index'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('resource show routes without a page no longer error with 500', function () {
    loginAs('admin');

    // URI yang sama dipakai PUT/DELETE, jadi GET menjawab 405 (bukan 500 karena method show() tidak ada)
    $this->get('/admin/users/1')->assertMethodNotAllowed();
    $this->get('/admin/roles/1')->assertMethodNotAllowed();
    $this->get('/templates/1')->assertMethodNotAllowed();
});

test('notifications are marked read and cleared through POST only', function () {
    loginAs();
    ScrapingNotification::notify('success', 'Judul', '<img src=x onerror=alert(1)>');

    // Parameter ?action lama tidak lagi mengubah data lewat GET
    $this->getJson(route('leads.scrape-status', ['action' => 'clear']))->assertOk()->assertJsonCount(1, 'notifications');

    $this->postJson(route('notifications.read'))->assertOk();
    expect(ScrapingNotification::first()->is_read)->toBeTrue();

    $this->postJson(route('notifications.clear'))->assertOk();
    expect(ScrapingNotification::count())->toBe(0);
});

test('the notification renderer escapes titles and messages', function () {
    loginAs();

    $html = $this->get(route('dashboard'))->getContent();

    expect($html)->toContain('window.escapeHtml(item.title)')
        ->toContain('window.escapeHtml(item.message)')
        ->not->toContain('${item.message}</p>');
});

test('crawling a website that resolves to a private address is refused', function (string $website) {
    loginAs();
    Process::fake();

    $lead = Lead::create(['business_name' => 'Internal', 'niche' => 'x', 'city' => 'Jakarta', 'source' => 'manual', 'website' => $website]);

    $this->postJson(route('leads.crawl-website', $lead))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    Process::assertNothingRan();
})->with([
    'loopback' => 'http://127.0.0.1/admin',
    'localhost' => 'http://localhost:8000',
    'private network' => 'http://192.168.1.10',
    'cloud metadata' => 'http://169.254.169.254/latest/meta-data',
    'non-http scheme' => 'file:///etc/passwd',
]);

test('crawling a public website fills missing contact details', function () {
    loginAs();
    Process::fake([
        '*' => Process::result('RESULT_JSON:{"success":true,"email":"Halo@Klinik.id","phone":"0812345678"}'),
    ]);

    $lead = Lead::create(['business_name' => 'Klinik', 'niche' => 'dentist', 'city' => 'Jakarta', 'source' => 'manual', 'website' => 'http://93.184.216.34']);

    $this->postJson(route('leads.crawl-website', $lead))
        ->assertOk()
        ->assertJsonPath('data.email', 'halo@klinik.id');

    expect($lead->fresh()->phone)->toBe('0812345678');

    // Secret .env tidak diteruskan ke proses Node
    Process::assertRan(function ($process) {
        return str_contains(implode(' ', (array) $process->command), 'crawl-website.js')
            && ($process->environment['APP_KEY'] ?? null) === false;
    });
});

test('manual leads reject duplicates and unsafe website schemes', function () {
    loginAs();
    Lead::create(['business_name' => 'Kopi Senja', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual']);

    $payload = ['business_name' => 'Kopi Senja', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual'];

    $this->post(route('leads.store'), $payload)->assertSessionHasErrors('business_name');
    $this->post(route('leads.store'), ['business_name' => 'Kopi Pagi', 'website' => 'javascript:alert(1)'] + $payload)->assertSessionHasErrors('website');

    $this->post(route('leads.store'), ['business_name' => 'Kopi Pagi', 'website' => 'kopipagi.id'] + $payload)->assertSessionHasNoErrors();
    expect(Lead::where('business_name', 'Kopi Pagi')->value('website'))->toBe('https://kopipagi.id');
});
