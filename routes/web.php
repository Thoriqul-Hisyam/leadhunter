<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CsvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\OutreachController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\WhatsAppWebhookController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Guest Routes (Auth Needed)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    // Maksimal 5 percobaan login per menit per IP (anti brute-force)
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    // Lupa password
    Route::get('forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'store'])->name('password.email')->middleware('throttle:5,1');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->name('password.update')->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Public: link unsubscribe di email (URL bertanda tangan)
|--------------------------------------------------------------------------
*/
Route::match(['get', 'post'], 'unsubscribe/{outreachMessage}', UnsubscribeController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('unsubscribe');

// Webhook WhatsApp gateway (balasan masuk & status kirim); token rahasia di URL
Route::match(['get', 'post'], 'webhooks/whatsapp/{token}', WhatsAppWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Notifikasi (lonceng)
    Route::get('leads/scrape-status', [LeadController::class, 'scrapeStatus'])->name('leads.scrape-status');
    Route::post('notifications/read', [LeadController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::post('notifications/clear', [LeadController::class, 'clearNotifications'])->name('notifications.clear');

    // Message Templates CRUD
    Route::middleware('can:manage_templates')->group(function () {
        Route::post('templates/generate', [MessageTemplateController::class, 'generate'])->name('templates.generate');
        Route::resource('templates', MessageTemplateController::class)->except('show');
        Route::post('templates/{template}/toggle', [MessageTemplateController::class, 'toggleStatus'])->name('templates.toggle');
    });

    // Campaigns & Lead Filters
    Route::middleware('can:manage_campaigns')->group(function () {
        Route::get('campaigns/leads/filter', [CampaignController::class, 'filterLeads'])->name('campaigns.leads.filter');
        Route::post('campaigns/suggest-leads', [CampaignController::class, 'suggestLeads'])->name('campaigns.suggest-leads');
        Route::get('campaigns/{campaign}/progress', [CampaignController::class, 'progress'])->name('campaigns.progress');
        Route::put('campaigns/{campaign}/sequence', [CampaignController::class, 'updateSequence'])->name('campaigns.sequence');
        Route::resource('campaigns', CampaignController::class);
    });

    // Leads Management, Scrapers & Pipeline
    Route::middleware('can:manage_leads')->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/export', [CsvController::class, 'exportLeads'])->name('leads.export');
        Route::post('leads/import', [CsvController::class, 'importLeads'])->name('leads.import');
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::post('leads/scrape', [LeadController::class, 'scrape'])->name('leads.scrape');
        Route::post('leads/bulk', [LeadController::class, 'bulk'])->name('leads.bulk');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::post('leads/{lead}/crawl-website', [LeadController::class, 'crawlWebsite'])->name('leads.crawl-website');
        Route::post('leads/{lead}/stage', [LeadController::class, 'updateStage'])->name('leads.stage');
        Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
        Route::delete('leads/{lead}/notes/{note}', [LeadController::class, 'destroyNote'])->name('leads.notes.destroy');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    });

    // Outreach Builder & AI Composer Pipeline
    Route::middleware('can:send_outreach')->group(function () {
        Route::get('outreach', [OutreachController::class, 'index'])->name('outreach.index');
        Route::get('outreach/export', [CsvController::class, 'exportOutreach'])->name('outreach.export');
        Route::post('outreach/compose/preview', [OutreachController::class, 'composePreview'])->name('outreach.compose.preview');
        Route::post('outreach/compose/save', [OutreachController::class, 'composeSave'])->name('outreach.compose.save');
        Route::post('outreach/compose/polish', [OutreachController::class, 'composePolish'])->name('outreach.compose.polish');
        Route::get('outreach/templates/filter', [OutreachController::class, 'getTemplates'])->name('outreach.templates.filter');
        Route::post('outreach/generate', [OutreachController::class, 'generate'])->name('outreach.generate');
        Route::post('outreach/bulk', [OutreachController::class, 'bulk'])->name('outreach.bulk');
        Route::post('outreach/{outreachMessage}/send', [OutreachController::class, 'send'])->name('outreach.send');
        Route::post('outreach/{outreachMessage}/status', [OutreachController::class, 'updateStatus'])->name('outreach.status');
        Route::post('outreach/{outreachMessage}/regenerate', [OutreachController::class, 'regenerate'])->name('outreach.regenerate');
        Route::put('outreach/{outreachMessage}', [OutreachController::class, 'update'])->name('outreach.update');
        Route::delete('outreach/{outreachMessage}', [OutreachController::class, 'destroy'])->name('outreach.destroy');
    });

    // Pengaturan pengirim, follow-up & blacklist
    Route::middleware('can:manage_settings')->group(function () {
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/email-preview', [SettingsController::class, 'emailPreview'])->name('settings.email-preview');
        Route::put('settings/connections', [SettingsController::class, 'updateConnections'])->name('settings.connections');
        Route::put('settings/sending', [SettingsController::class, 'updateSending'])->name('settings.sending');
        Route::post('settings/test-whatsapp', [SettingsController::class, 'testWhatsApp'])->name('settings.test-whatsapp')->middleware('throttle:5,1');
        Route::post('settings/test-ai', [SettingsController::class, 'testAi'])->name('settings.test-ai');
        Route::post('settings/test-mail', [SettingsController::class, 'testMail'])->name('settings.test-mail')->middleware('throttle:5,1');
        Route::post('settings/test-imap', [SettingsController::class, 'testImap'])->name('settings.test-imap')->middleware('throttle:5,1');
        Route::post('settings/blacklist', [SettingsController::class, 'storeBlacklist'])->name('blacklist.store');
        Route::delete('settings/blacklist/{entry}', [SettingsController::class, 'destroyBlacklist'])->name('blacklist.destroy');

        // Antrean & worker
        Route::get('queue', [QueueController::class, 'index'])->name('queue.index');
        Route::post('queue/failed/retry-all', [QueueController::class, 'retryAll'])->name('queue.retry-all');
        Route::post('queue/failed/{uuid}/retry', [QueueController::class, 'retry'])->name('queue.retry');
        Route::delete('queue/failed/{uuid}', [QueueController::class, 'forget'])->name('queue.forget');
        Route::delete('queue/failed', [QueueController::class, 'flush'])->name('queue.flush');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin: User & Role Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::resource('users', UserController::class)->except('show')->middleware('can:manage_users');
        Route::resource('roles', RoleController::class)->except('show')->middleware('can:manage_roles');
    });
});
