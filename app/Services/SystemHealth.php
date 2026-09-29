<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pantau apakah scheduler dan queue worker benar-benar berjalan.
 * Scheduler menulis detak tiap menit dan mengirim HeartbeatJob ke setiap antrean;
 * worker menulis detak setiap kali memproses job.
 */
class SystemHealth
{
    public const QUEUES = ['default', 'scraping'];

    /** Detak lebih lama dari ini dianggap mati. */
    public const STALE_AFTER_SECONDS = 180;

    public static function beatScheduler(): void
    {
        Cache::forever('heartbeat:scheduler', now()->timestamp);
    }

    public static function beatQueue(?string $queue): void
    {
        Cache::forever('heartbeat:queue:'.($queue ?: 'default'), now()->timestamp);
    }

    /**
     * @return array<string, array{label: string, last_seen: ?Carbon, healthy: bool, busy: bool}>
     */
    public static function status(): array
    {
        try {
            $components = ['scheduler' => ['label' => 'Scheduler', 'key' => 'heartbeat:scheduler', 'queue' => null]];
            foreach (self::QUEUES as $queue) {
                $components["queue:{$queue}"] = ['label' => "Queue worker \"{$queue}\"", 'key' => "heartbeat:queue:{$queue}", 'queue' => $queue];
            }

            $status = [];
            foreach ($components as $name => $component) {
                $timestamp = Cache::get($component['key']);
                $lastSeen = $timestamp ? Carbon::createFromTimestamp($timestamp)->setTimezone(config('app.timezone')) : null;
                $fresh = $lastSeen && $lastSeen->diffInSeconds(now()) <= self::STALE_AFTER_SECONDS;

                // Worker yang sedang menjalankan job panjang (scraping 15 menit) tidak mengirim detak, tapi tidak mati.
                $busy = $component['queue'] && ! $fresh && static::isBusy($component['queue']);

                // Queue "sync" (mis. saat test) tidak butuh worker.
                $syncQueue = $component['queue'] && config('queue.default') === 'sync';

                $status[$name] = [
                    'label' => $component['label'],
                    'last_seen' => $lastSeen,
                    'healthy' => $fresh || $busy || $syncQueue,
                    'busy' => $busy,
                ];
            }

            return $status;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, string> label komponen yang mati
     */
    public static function problems(): array
    {
        return array_values(static::unhealthy());
    }

    /**
     * @return array<string, string> komponen yang mati: nama (scheduler, queue:default, ...) => label
     */
    public static function unhealthy(): array
    {
        return collect(static::status())
            ->reject(fn ($component) => $component['healthy'])
            ->map(fn ($component) => $component['label'])
            ->all();
    }

    /**
     * Cara menyalakan satu komponen. Lokal: semuanya lewat `composer run dev`. Server: setiap proses
     * berdiri sendiri (worker lewat Supervisor, scheduler lewat cron), sama dengan docs/deployment.md.
     *
     * @return array{via: string, short: string, command: string}
     */
    public static function runHint(string $component): array
    {
        if (app()->isLocal()) {
            return ['via' => 'lokal', 'short' => 'composer run dev', 'command' => 'composer run dev'];
        }

        $artisan = base_path('artisan');

        return match ($component) {
            'scheduler' => ['via' => 'cron', 'short' => 'schedule:run', 'command' => '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1'],
            'queue:scraping' => ['via' => 'Supervisor', 'short' => 'queue:work --queue=scraping', 'command' => "php {$artisan} queue:work --queue=scraping --sleep=5 --tries=1 --timeout=900 --max-time=7200"],
            default => ['via' => 'Supervisor', 'short' => 'queue:work --queue=default', 'command' => "php {$artisan} queue:work --queue=default --sleep=3 --tries=1 --timeout=300 --max-time=3600"],
        };
    }

    protected static function isBusy(string $queue): bool
    {
        if (config('queue.default') !== 'database') {
            return false;
        }

        return DB::table(config('queue.connections.database.table', 'jobs'))
            ->where('queue', $queue)
            ->whereNotNull('reserved_at')
            ->exists();
    }
}
