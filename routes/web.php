<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\OutreachController;
use App\Http\Controllers\MessageTemplateController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LocaleController;

/*
|--------------------------------------------------------------------------
| Guest Routes (Auth Needed)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Message Templates CRUD
    Route::resource('templates', MessageTemplateController::class);
    Route::post('templates/{template}/toggle', [MessageTemplateController::class, 'toggleStatus'])->name('templates.toggle');

    // Campaigns & Lead Filters
    Route::get('campaigns/leads/filter', [CampaignController::class, 'filterLeads'])->name('campaigns.leads.filter');
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/suggest-leads', [CampaignController::class, 'suggestLeads'])->name('campaigns.suggest-leads');

    // Leads Management & Scrapers
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/scrape-status', [LeadController::class, 'scrapeStatus'])->name('leads.scrape-status');
    Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
    Route::post('leads/scrape', [LeadController::class, 'scrape'])->name('leads.scrape');
    Route::post('leads/{lead}/crawl-website', [LeadController::class, 'crawlWebsite'])->name('leads.crawl-website');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // Outreach Builder & AI Composer Pipeline
    Route::get('outreach', [OutreachController::class, 'index'])->name('outreach.index');
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

    /*
    |--------------------------------------------------------------------------
    | Admin Only RBAC / Management Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
    });
});
