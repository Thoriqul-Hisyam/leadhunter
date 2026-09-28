<?php

namespace App\Console\Commands;

use App\Models\OutreachMessage;
use App\Services\MessageQualityGate;
use Illuminate\Console\Command;

class CheckDrafts extends Command
{
    protected $signature = 'outreach:check-drafts {--dry-run : Tampilkan hasil tanpa menandai pesan}';

    protected $description = 'Jalankan quality gate ke draft AI/hybrid yang sudah ada dan tandai yang bermasalah sebagai "perlu review"';

    public function handle(MessageQualityGate $gate): int
    {
        $flagged = 0;
        $checked = 0;

        OutreachMessage::with('lead')
            ->where('status', 'pending')
            ->whereIn('mode', ['ai', 'hybrid'])
            ->where('needs_review', false)
            ->chunkById(200, function ($messages) use ($gate, &$flagged, &$checked) {
                foreach ($messages as $message) {
                    $checked++;
                    $problems = $gate->problems((string) $message->message, $message->subject, $message->lead, $message->type);

                    if (! $problems) {
                        continue;
                    }

                    $flagged++;
                    $this->line("#{$message->id} {$message->lead?->displayName()} ({$message->type}): ".implode('; ', $problems));

                    if (! $this->option('dry-run')) {
                        $message->update(['needs_review' => true]);
                    }
                }
            });

        $this->info("{$checked} draft diperiksa, {$flagged} ".($this->option('dry-run') ? 'akan ditandai' : 'ditandai').' perlu review.');

        return self::SUCCESS;
    }
}
