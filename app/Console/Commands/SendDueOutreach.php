<?php

namespace App\Console\Commands;

use App\Services\OutreachSender;
use Illuminate\Console\Command;

class SendDueOutreach extends Command
{
    protected $signature = 'outreach:send-due';

    protected $description = 'Kirim email antrean yang sudah jatuh tempo (mematuhi batas kirim per jam)';

    public function handle(OutreachSender $sender): int
    {
        $count = $sender->dispatchDue();

        if ($count > 0) {
            $this->info("{$count} email diserahkan ke queue worker.");
        }

        return self::SUCCESS;
    }
}
