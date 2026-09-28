<?php

namespace App\Jobs;

use App\Services\SystemHealth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Job kosong yang dikirim scheduler tiap menit ke setiap antrean.
 * Jika worker mati, job ini tidak diproses dan detaknya berhenti.
 * Unik per antrean supaya tidak menumpuk saat worker mati.
 */
class HeartbeatJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 1;

    public $uniqueFor = 600;

    public function __construct(public string $queueName)
    {
        $this->onQueue($queueName);
    }

    public function uniqueId(): string
    {
        return $this->queueName;
    }

    public function handle(): void
    {
        SystemHealth::beatQueue($this->queueName);
    }
}
