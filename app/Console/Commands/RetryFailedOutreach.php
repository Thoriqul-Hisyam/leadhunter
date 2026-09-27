<?php

namespace App\Console\Commands;

use App\Services\OutreachSender;
use Illuminate\Console\Command;

class RetryFailedOutreach extends Command
{
    protected $signature = 'outreach:retry-failed';

    protected $description = 'Masukkan ulang email yang gagal karena error sementara ke antrean kirim';

    public function handle(OutreachSender $sender): int
    {
        $count = $sender->retryFailed();

        $this->info("{$count} email dijadwalkan ulang.");

        return self::SUCCESS;
    }
}
