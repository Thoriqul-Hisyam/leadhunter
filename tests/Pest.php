<?php

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Test tidak boleh memanggil AI, Google, atau Apify sungguhan.
        Http::preventStrayRequests();
    })
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Login sebagai user baru dengan role tertentu (role & permission dari seeder).
 */
function loginAs(?string $role = 'admin'): User
{
    test()->seed(RoleAndAdminSeeder::class);

    $user = User::factory()->create();

    if ($role) {
        $user->assignRole($role);
    }

    test()->actingAs($user);

    return $user;
}

/**
 * Aktifkan AI dengan key palsu (panggilan HTTP-nya di-fake per test).
 */
function fakeAiConfigured(): void
{
    config([
        'services.ai.key' => 'test-key',
        'services.ai.base_url' => 'https://ai.test/v1',
        'services.ai.model' => 'test-model',
        'services.ai.retries' => 0,
    ]);
}

function aiResponse(string $content): array
{
    return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
}
