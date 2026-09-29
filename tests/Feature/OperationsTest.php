<?php

use App\Jobs\HeartbeatJob;
use App\Jobs\ScrapeGoogleMapsJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use App\Services\Scraping\LeadScraperService;
use App\Services\SystemHealth;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

test('the application runs in Jakarta time by default', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Jakarta');
});

test('system health reports fresh, stale and busy components', function () {
    config(['queue.default' => 'database']);

    SystemHealth::beatScheduler();
    SystemHealth::beatQueue('default');
    Cache::forever('heartbeat:queue:scraping', now()->subMinutes(10)->timestamp);

    expect(SystemHealth::problems())->toBe(['Queue worker "scraping"']);

    // Worker scraping sedang memproses job panjang → sibuk, bukan mati
    DB::table('jobs')->insert(['queue' => 'scraping', 'payload' => '{}', 'attempts' => 1, 'reserved_at' => now()->timestamp, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp]);
    expect(SystemHealth::problems())->toBe([])
        ->and(SystemHealth::status()['queue:scraping']['busy'])->toBeTrue();

    Cache::forever('heartbeat:scheduler', now()->subMinutes(5)->timestamp);
    expect(SystemHealth::problems())->toContain('Scheduler');
});

test('heartbeat jobs record the queue they ran on', function () {
    (new HeartbeatJob('scraping'))->handle();

    expect(SystemHealth::status()['queue:scraping']['last_seen'])->not->toBeNull();
});

test('the layout warns when the scheduler is not running', function () {
    loginAs();
    Cache::forget('heartbeat:scheduler');

    $this->get(route('dashboard'))->assertSee('Scheduler')->assertSee('tidak berjalan');

    SystemHealth::beatScheduler();
    $this->get(route('dashboard'))->assertDontSee('tidak berjalan. Scraping');
});

test('run instructions say composer run dev locally and point to cron or supervisor on a server', function () {
    loginAs('admin');
    config(['queue.default' => 'database']);
    Cache::forget('heartbeat:scheduler');
    SystemHealth::beatQueue('default');
    Cache::forget('heartbeat:queue:scraping');

    app()['env'] = 'local';
    $this->get(route('dashboard'))->assertSee('composer run dev');

    app()['env'] = 'production';
    $this->get(route('dashboard'))->assertOk()
        ->assertDontSee('composer run dev')
        ->assertSee('cron <code>schedule:run</code>', false)
        ->assertSee('Supervisor <code>queue:work --queue=scraping</code>', false)
        ->assertDontSee('queue:work --queue=default');

    $this->get(route('queue.index'))->assertOk()
        ->assertDontSee('composer run dev')
        ->assertSee('cd '.base_path().' &amp;&amp; php artisan schedule:run', false)
        ->assertSee('queue:work --queue=scraping --sleep=5 --tries=1 --timeout=900');
});

test('failed jobs can be viewed, retried and forgotten from the queue page', function () {
    loginAs('admin');
    DB::table('failed_jobs')->insert([
        'uuid' => 'abc-123', 'connection' => 'database', 'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\SendOutreachJob', 'uuid' => 'abc-123', 'job' => 'x', 'data' => []]),
        'exception' => "RuntimeException: SMTP timeout\n#0 stack", 'failed_at' => now(),
    ]);

    $this->get(route('queue.index'))->assertOk()->assertSee('SendOutreachJob')->assertSee('SMTP timeout');

    $this->delete(route('queue.forget', 'abc-123'))->assertRedirect(route('queue.index'));
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

test('the queue page requires the settings permission', function () {
    loginAs('user');

    $this->get(route('queue.index'))->assertForbidden();
});

test('records remember who created them, also from background jobs', function () {
    $user = loginAs();

    $campaign = Campaign::create(['name' => 'C', 'niche' => 'cafe', 'location' => 'Bali']);
    expect($campaign->created_by)->toBe($user->id)
        ->and($campaign->creator->is($user))->toBeTrue();

    Queue::fake();
    $this->post(route('leads.scrape'), ['niche' => 'cafe', 'location' => 'bali']);
    Queue::assertPushed(ScrapeGoogleMapsJob::class, fn ($job) => $job->userId === $user->id);

    auth()->logout();
    Process::fake(['*' => Process::result('LEAD_ROW:'.json_encode(['name' => 'Kopi Job']))]);
    app(LeadScraperService::class)->scrape('cafe', 'bali', $user->id);

    expect(Lead::where('business_name', 'Kopi Job')->value('created_by'))->toBe($user->id);
});

test('the onboarding checklist shows until every step is done', function () {
    loginAs('admin');

    $this->get(route('dashboard'))->assertSee('Mulai di sini')->assertSee('Hubungkan AI');

    fakeAiConfigured();
    config(['mail.default' => 'smtp']);
    Setting::put([
        'sender_name' => 'Budi', 'ai_tested_at' => now(), 'mail_tested_at' => now(),
        'wa_driver' => 'fonnte', 'wa_tested_at' => now(),
    ]);
    Lead::create(['business_name' => 'X', 'niche' => 'x', 'city' => 'Y', 'source' => 'manual']);
    Campaign::create(['name' => 'C', 'niche' => 'x', 'location' => 'Y']);

    $this->get(route('dashboard'))->assertDontSee('Mulai di sini');
});

test('users can reset a forgotten password by email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'lupa@example.com']);

    $this->get(route('login'))->assertSee('Lupa password?');
    $this->post(route('password.email'), ['email' => 'lupa@example.com'])->assertSessionHas('success');
    // Email tidak terdaftar mendapat pesan yang sama
    $this->post(route('password.email'), ['email' => 'tidakada@example.com'])->assertSessionHas('success');

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->get(route('password.reset', ['token' => $token, 'email' => 'lupa@example.com']))->assertOk()->assertSee('Buat password baru');

    $this->post(route('password.update'), [
        'token' => $token, 'email' => 'lupa@example.com',
        'password' => 'password-baru-123', 'password_confirmation' => 'password-baru-123',
    ])->assertRedirect(route('login'));

    $this->post('/login', ['email' => 'lupa@example.com', 'password' => 'password-baru-123'])->assertRedirect(route('dashboard'));
});

test('the pipeline can be searched and filtered, also as a live partial', function () {
    loginAs();
    Lead::create(['business_name' => 'Klinik Aurora', 'niche' => 'klinik', 'city' => 'Surabaya', 'source' => 'manual', 'phone' => '081234']);
    Lead::create(['business_name' => 'Kopi Senja', 'niche' => 'cafe', 'city' => 'Bali', 'source' => 'manual', 'pipeline_stage' => 'meeting']);

    $this->get(route('pipeline.index', ['search' => 'aurora']))
        ->assertOk()->assertSee('Klinik Aurora')->assertDontSee('Kopi Senja')->assertSee('1 lead cocok');

    $this->get(route('pipeline.index', ['search' => '081234']))->assertSee('Klinik Aurora');

    $this->get(route('pipeline.index', ['niche' => 'cafe', 'partial' => 1]))
        ->assertOk()
        ->assertSee('Kopi Senja')
        ->assertDontSee('Klinik Aurora')
        ->assertDontSee('<html', false); // hanya papan, bukan halaman penuh
});
