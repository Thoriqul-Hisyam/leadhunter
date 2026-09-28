<?php

namespace App\Http\Controllers;

use App\Services\SystemHealth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Halaman Antrean: status worker/scheduler, job yang menunggu, dan job gagal (retry/hapus dari UI).
 */
class QueueController extends Controller
{
    public function index()
    {
        $health = SystemHealth::status();

        $pending = DB::table('jobs')
            ->selectRaw('queue, COUNT(*) as total, SUM(CASE WHEN reserved_at IS NOT NULL THEN 1 ELSE 0 END) as running, MIN(available_at) as oldest')
            ->groupBy('queue')
            ->get();

        $failed = DB::table('failed_jobs')->orderByDesc('failed_at')->paginate(20);
        $failed->getCollection()->transform(function ($job) {
            $payload = json_decode($job->payload, true) ?: [];

            return (object) [
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'name' => class_basename($payload['displayName'] ?? 'Job'),
                'error' => Str::limit(strtok((string) $job->exception, "\n"), 220),
                'failed_at' => $job->failed_at,
            ];
        });

        return view('queue.index', compact('health', 'pending', 'failed'));
    }

    public function retry(string $uuid)
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return redirect()->route('queue.index')->with('success', 'Job dimasukkan kembali ke antrean.');
    }

    public function retryAll()
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return redirect()->route('queue.index')->with('success', 'Semua job gagal dimasukkan kembali ke antrean.');
    }

    public function forget(string $uuid)
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        return redirect()->route('queue.index')->with('success', 'Job gagal dihapus.');
    }

    public function flush()
    {
        Artisan::call('queue:flush');

        return redirect()->route('queue.index')->with('success', 'Semua job gagal dihapus.');
    }
}
