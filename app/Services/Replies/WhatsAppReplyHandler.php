<?php

namespace App\Services\Replies;

use App\Jobs\ClassifyReplyJob;
use App\Models\BlacklistEntry;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;

/**
 * Proses event webhook WhatsApp gateway: balasan masuk dan status pengiriman.
 */
class WhatsAppReplyHandler
{
    public function __construct(protected ReplyClassifier $classifier)
    {
    }

    /**
     * @param  array<int, array>  $events  hasil WhatsAppGateway::parseWebhook()
     * @return array{replied: int, unsubscribed: int, auto_reply: int, failed: int}
     */
    public function handle(array $events): array
    {
        $result = ['replied' => 0, 'unsubscribed' => 0, 'auto_reply' => 0, 'failed' => 0];

        foreach ($events as $event) {
            if ($event['type'] === 'status') {
                $result['failed'] += $this->handleStatus($event) ? 1 : 0;

                continue;
            }

            $outcome = $this->handleMessage($event['from'], $event['text']);
            if ($outcome) {
                $result[$outcome]++;
            }
        }

        return $result;
    }

    /**
     * @return 'replied'|'unsubscribed'|'auto_reply'|null
     */
    public function handleMessage(string $from, string $text): ?string
    {
        $lead = Lead::where('whatsapp_number', $from)->first();

        if (! $lead) {
            return null;
        }

        $message = OutreachMessage::where('lead_id', $lead->id)
            ->whereIn('status', ['sent', 'replied'])
            ->orderByRaw("CASE WHEN type = 'whatsapp' THEN 0 ELSE 1 END")
            ->latest('sent_at')
            ->first();

        // Pesan dari nomor yang belum pernah kita hubungi bukan balasan outreach.
        if (! $message) {
            return null;
        }

        if ($this->isStopRequest($text)) {
            BlacklistEntry::add('phone', $from, 'unsubscribe (balasan WhatsApp)');

            // Minta berhenti = berhenti di semua kanal, bukan hanya WhatsApp.
            OutreachMessage::cancelOpenFor($lead->id, 'Penerima minta berhenti dihubungi.');

            if ($lead->pipeline_stage !== 'deal') {
                $lead->update(['pipeline_stage' => 'lost']);
            }

            return 'unsubscribed';
        }

        if ($message->status === 'sent') {
            // Sapaan/menu otomatis WhatsApp Business bukan balasan sungguhan.
            if ($this->classifier->isAutoReply(null, $text)) {
                $message->update(['reply_category' => 'auto_reply', 'reply_excerpt' => mb_substr(trim($text), 0, 500)]);

                return 'auto_reply';
            }

            $message->markReplied();
            $message->update(['reply_excerpt' => mb_substr(trim($text), 0, 500), 'reply_category' => null]);
            ClassifyReplyJob::dispatch($message->id);

            ScrapingNotification::notify('success', 'Balasan WhatsApp', "{$lead->displayName()} membalas: \"".mb_strimwidth(trim($text), 0, 80, '…').'"');

            return 'replied';
        }

        return null;
    }

    public function isStopRequest(string $text): bool
    {
        $text = trim(mb_strtolower($text));

        return mb_strlen($text) <= 60 && (bool) preg_match('/^(stop|berhenti|unsubscribe|jangan hubungi|jangan kirim)\b/u', $text);
    }

    protected function handleStatus(array $event): bool
    {
        if (! $event['failed']) {
            return false;
        }

        $message = OutreachMessage::where('type', 'whatsapp')->where('message_id', $event['id'])->first();

        if (! $message || $message->status === 'replied') {
            return false;
        }

        $message->markFailed("Gateway melaporkan status \"{$event['status']}\".");

        return true;
    }
}
