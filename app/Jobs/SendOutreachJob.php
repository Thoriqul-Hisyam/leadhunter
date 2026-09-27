<?php

namespace App\Jobs;

use App\Exceptions\OutreachSendException;
use App\Models\OutreachMessage;
use App\Services\OutreachSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Kirim satu email dari antrean. Retry diatur oleh scheduler (OutreachSender::retryFailed),
 * bukan oleh queue, agar tetap mematuhi batas kirim per jam.
 */
class SendOutreachJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 120;

    public function __construct(public int $messageId)
    {
    }

    public function handle(OutreachSender $sender): void
    {
        $message = OutreachMessage::find($this->messageId);

        // Pengguna mungkin membatalkan antrean (reset ke pending) sebelum worker sempat jalan.
        if (! $message || $message->status !== 'queued') {
            return;
        }

        try {
            $sender->sendEmail($message);
        } catch (OutreachSendException $e) {
            Log::warning("Outreach #{$message->id} gagal dikirim: {$e->getMessage()}");
        }
    }
}
