<?php

namespace App\Console\Commands;

use App\Services\FollowupService;
use Illuminate\Console\Command;

class GenerateFollowups extends Command
{
    protected $signature = 'outreach:followups {--limit=20 : Maksimal follow-up per eksekusi}';

    protected $description = 'Buat langkah follow-up (sequence) untuk outreach yang belum dibalas setelah N hari';

    public function handle(FollowupService $followups): int
    {
        $count = $followups->generateDue((int) $this->option('limit'));

        $this->info("{$count} follow-up dibuat.");

        return self::SUCCESS;
    }
}
