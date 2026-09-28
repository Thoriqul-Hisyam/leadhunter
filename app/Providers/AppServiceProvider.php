<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use App\Services\RuntimeConfig;
use App\Services\SystemHealth;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Koneksi AI & email dari halaman Pengaturan (database), juga di-refresh sebelum setiap job queue.
        RuntimeConfig::apply();
        Queue::before(fn () => RuntimeConfig::apply());

        // Setiap job yang selesai = bukti worker antrean itu hidup (dipakai banner status di layout).
        Queue::after(fn (JobProcessed $event) => SystemHealth::beatQueue($event->job->getQueue()));

        // Di balik reverse proxy/HTTPS, link yang dibuat (termasuk link unsubscribe di email) harus tetap https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Permission RBAC dari database → bisa dipakai sebagai middleware "can:manage_leads" dan @can di Blade.
        Gate::before(function (User $user, string $ability) {
            if (in_array($ability, Permission::SLUGS, true)) {
                return $user->hasPermission($ability);
            }

            return null;
        });
    }
}
