<?php

namespace App\Services\Replies;

use App\Models\BlacklistEntry;
use App\Models\OutreachMessage;
use Illuminate\Support\Carbon;

/**
 * Cocokkan email masuk dengan outreach yang sudah terkirim, lalu tandai "replied".
 * Prioritas: header In-Reply-To/References (Message-ID kita), lalu alamat pengirim.
 * Balasan berisi permintaan berhenti (BERHENTI / unsubscribe) masuk blacklist, bukan dihitung reply.
 */
class ReplyDetector
{
    /** @var int jumlah permintaan berhenti yang diproses pada pemanggilan terakhir */
    public int $unsubscribed = 0;

    /**
     * @param  iterable<array{from: ?string, subject?: ?string, in_reply_to?: ?string, references?: array|string|null, date?: ?Carbon, body?: ?string}>  $emails
     * @return int jumlah pesan yang ditandai replied
     */
    public function process(iterable $emails): int
    {
        $matched = 0;
        $this->unsubscribed = 0;

        foreach ($emails as $email) {
            $message = $this->match($email);

            if (! $message) {
                continue;
            }

            if ($this->isUnsubscribeRequest($email)) {
                $this->unsubscribe($message, $email['from'] ?? null);

                continue;
            }

            if ($message->status !== 'sent') {
                continue;
            }

            $date = $email['date'] ?? null;

            // Email yang lebih tua dari pesan kita bukan balasannya.
            if ($date && $message->sent_at && $date->lt($message->sent_at)) {
                continue;
            }

            $message->markReplied($date);
            $matched++;
        }

        return $matched;
    }

    public function match(array $email): ?OutreachMessage
    {
        $ids = $this->messageIds($email);

        if ($ids) {
            $message = OutreachMessage::whereIn('message_id', $ids)->first();
            if ($message) {
                return $message;
            }
        }

        $from = strtolower(trim((string) ($email['from'] ?? '')));

        if ($from === '') {
            return null;
        }

        return OutreachMessage::where('type', 'email')
            ->whereIn('status', ['sent', 'replied'])
            ->whereHas('lead', fn ($q) => $q->whereRaw('LOWER(email) = ?', [$from]))
            ->orderByRaw("CASE WHEN status = 'sent' THEN 0 ELSE 1 END")
            ->latest('sent_at')
            ->first();
    }

    /**
     * Subjek "unsubscribe"/"berhenti"/"stop", atau balasan singkat yang isinya kata tersebut
     * (teks kutipan email kita di bawahnya diabaikan).
     */
    public function isUnsubscribeRequest(array $email): bool
    {
        $keyword = '/\b(unsubscribe|berhenti|stop)\b/iu';
        $subject = preg_replace('/^\s*((re|fw|fwd|balas)\s*:\s*)+/iu', '', (string) ($email['subject'] ?? ''));

        if (preg_match('/^\s*(unsubscribe|berhenti|stop)\b/iu', $subject)) {
            return true;
        }

        $body = (string) ($email['body'] ?? '');
        if ($body === '') {
            return false;
        }

        // Ambil hanya balasan baru, sebelum kutipan pesan asli.
        $reply = [];
        foreach (preg_split('/\r?\n/', $body) as $line) {
            if (str_starts_with(ltrim($line), '>') || preg_match('/(wrote|menulis)\s*:\s*$/iu', $line) || preg_match('/^-{2,}\s*(original|pesan asli)/iu', $line)) {
                break;
            }
            $reply[] = $line;
        }
        $reply = trim(implode(' ', $reply));

        return $reply !== '' && mb_strlen($reply) <= 200 && preg_match($keyword, $reply) === 1;
    }

    protected function unsubscribe(OutreachMessage $message, ?string $from): void
    {
        $email = $from ?: $message->lead?->email;

        if (! $email) {
            return;
        }

        BlacklistEntry::add('email', $email, 'unsubscribe (balasan)');

        OutreachMessage::where('lead_id', $message->lead_id)
            ->where('type', 'email')
            ->whereIn('status', ['queued', 'pending'])
            ->update(['status' => 'failed', 'scheduled_at' => null, 'last_error' => 'Penerima minta berhenti dihubungi.']);

        if ($message->lead && $message->lead->pipeline_stage !== 'deal') {
            $message->lead->update(['pipeline_stage' => 'lost']);
        }

        $this->unsubscribed++;
    }

    protected function messageIds(array $email): array
    {
        $references = $email['references'] ?? [];
        if (is_string($references)) {
            $references = preg_split('/\s+/', $references);
        }

        $ids = array_merge([(string) ($email['in_reply_to'] ?? '')], (array) $references);

        return array_values(array_unique(array_filter(array_map(fn ($id) => trim((string) $id, " <>\t\r\n"), $ids))));
    }
}
