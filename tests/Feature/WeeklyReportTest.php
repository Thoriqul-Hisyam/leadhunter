<?php

use App\Mail\WeeklyReportMail;
use App\Models\BlacklistEntry;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use App\Models\User;
use App\Services\WeeklyReport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = loginAs('admin');
});

function reportData(): Campaign
{
    $campaign = Campaign::create(['name' => 'Klinik Surabaya', 'niche' => 'klinik', 'location' => 'Surabaya']);
    $hot = Lead::create(['business_name' => 'Klinik Hot', 'niche' => 'klinik', 'city' => 'Surabaya', 'source' => 'manual', 'rating' => 4.9, 'reviews_count' => 400, 'phone' => '0812 3456 7890', 'email' => 'hot@klinik.id']);
    $other = Lead::create(['business_name' => 'Klinik Biasa', 'niche' => 'klinik', 'city' => 'Surabaya', 'source' => 'manual', 'website' => 'https://biasa.id', 'email' => 'b@klinik.id']);

    $base = ['campaign_id' => $campaign->id, 'message' => 'x', 'sent_at' => now()->subDays(2)];
    OutreachMessage::create($base + ['lead_id' => $other->id, 'type' => 'email', 'status' => 'replied', 'replied_at' => now()->subDay(), 'reply_category' => 'pricing']);
    OutreachMessage::create($base + ['lead_id' => $other->id, 'type' => 'whatsapp', 'status' => 'sent']);
    OutreachMessage::create(['sent_at' => now()->subDays(20)] + $base + ['lead_id' => $hot->id, 'type' => 'email', 'status' => 'sent']); // di luar periode
    OutreachMessage::create(['lead_id' => $hot->id, 'campaign_id' => $campaign->id, 'type' => 'email', 'message' => 'draft', 'status' => 'pending', 'needs_review' => true]);
    BlacklistEntry::add('email', 'x@bounce.id', 'bounce');

    return $campaign;
}

test('the weekly report summarises the last seven days', function () {
    reportData();

    $report = app(WeeklyReport::class)->build();

    expect($report['outreach'])->toMatchArray(['email' => 1, 'whatsapp' => 1, 'replied' => 1, 'reply_rate' => 50.0, 'bounced' => 1])
        ->and($report['leads'])->toMatchArray(['new' => 2, 'hot' => 1, 'total' => 2])
        ->and($report['categories']['pricing'])->toBe(1)
        ->and($report['todo'])->toMatchArray(['needs_review' => 1, 'pending' => 1, 'hot_uncontacted' => 1])
        ->and($report['campaigns']->first()->name)->toBe('Klinik Surabaya');
});

test('the weekly report is emailed to admins and can be turned off', function () {
    Mail::fake();
    reportData();
    $member = User::factory()->create(['email' => 'member@leadhunter.test']);
    $member->assignRole('user');

    $this->artisan('leadhunter:weekly-report')->expectsOutputToContain('Laporan mingguan dikirim')->assertSuccessful();

    Mail::assertSent(WeeklyReportMail::class, fn ($mail) => $mail->hasTo($this->admin->email)
        && ! $mail->hasTo('member@leadhunter.test')
        && str_contains($mail->render(), 'Tanya harga')
        && str_contains($mail->render(), 'Lead Hot yang belum dihubungi'));

    Setting::put(['weekly_report_enabled' => '0']);
    $this->artisan('leadhunter:weekly-report')->expectsOutputToContain('nonaktif')->assertSuccessful();
    Mail::assertSentCount(1);
});

test('the weekly report is scheduled for monday morning', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command ?? '', 'leadhunter:weekly-report'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 7 * * 1');
});
