<?php

namespace App\Jobs;

use App\Models\OutreachMessage;
use App\Services\Replies\ReplyClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Klasifikasi balasan di background (panggilan AI bisa memakan waktu puluhan detik).
 */
class ClassifyReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 300;

    public function __construct(public int $messageId, public ?string $subject = null)
    {
    }

    public function handle(ReplyClassifier $classifier): void
    {
        $message = OutreachMessage::find($this->messageId);

        if (! $message || $message->status !== 'replied' || $message->reply_category) {
            return;
        }

        $classifier->apply($message, $classifier->classify($this->subject, $message->reply_excerpt));
    }
}
