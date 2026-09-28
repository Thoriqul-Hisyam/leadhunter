<?php

namespace App\Services\Replies;

use App\Jobs\ClassifyReplyJob;
use App\Models\BlacklistEntry;
use App\Models\OutreachMessage;
use Illuminate\Support\Carbon;

/**
 * Cocokkan email masuk dengan outreach yang sudah terkirim, lalu tandai "replied".
 * Prioritas: header In-Reply-To/References (Message-ID kita), lalu alamat pengirim.
 * - Bounce (mailer-daemon) → pesan gagal + alamat masuk blacklist "bounce".
 * - Permintaan berhenti (BERHENTI / unsubscribe) → blacklist, bukan dihitung reply.
 * - Balasan otomatis (out of office) → tidak dihitung reply.
 * Balasan sungguhan diklasifikasikan di background (ClassifyReplyJob).
 */
class ReplyDetector
{
    /** @var int jumlah permintaan berhenti yang diproses pada pemanggilan terakhir */
    public int $unsubscribed = 0;

    /** @var int jumlah email bounce yang diproses pada pemanggilan terakhir */
    public int $bounced = 0;

    /** @var int jumlah balasan otomatis yang diabaikan pada pemanggilan terakhir */
    public int $autoReplies = 0;

    public function __construct(protected ReplyClassifier $classifier)
    {
    }

    /**
     * @param  iterable<array{from: ?string, subject?: ?string, in_reply_to?: ?string, references?: array|string|null, date?: ?Carbon, body?: ?string}>  $emails
     * @return int jumlah pesan yang ditandai replied
     */
    public function process(iterable $emails): int
    {
        $matched = 0;
        $this->unsubscribed = $this->bounced = $this->autoReplies = 0;

        foreach ($emails as $email) {
            // Bounce dicek lebih dulu: laporan gagal kirim dari Gmail membawa In-Reply-To pesan kita.
            if ($this->isBounce($email)) {
                $this->bounce($email);

                continue;
            }

            // Notifikasi sistem lain (mis. "Delay") bukan balasan dari lead.
            if ($this->isSystemSender($email)) {
                continue;
            }

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

            $text = $this->replyText((string) ($email['body'] ?? ''));

            if ($this->classifier->isAutoReply($email['subject'] ?? null, $text)) {
                $message->update(['reply_category' => 'auto_reply']);
                $this->autoReplies++;

                continue;
            }

            $message->markReplied($date);
            $message->update(['reply_excerpt' => $text !== '' ? mb_substr($text, 0, 500) : null, 'reply_category' => null]);
            ClassifyReplyJob::dispatch($message->id, $email['subject'] ?? null);
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
     * Laporan gagal kirim permanen (bukan "Delay"/penundaan sementara).
     */
    public function isBounce(array $email): bool
    {
        $from = strtolower(trim((string) ($email['from'] ?? '')));
        $subject = (string) ($email['subject'] ?? '');

        if (preg_match('/\b(delay(ed)?|tertunda|warning)\b/iu', $subject)) {
            return false;
        }

        return $this->isSystemSender($email)
            || (bool) preg_match('/(delivery status notification \(failure\)|undeliver(able|ed)|mail delivery (failed|failure)|returned mail|failure notice|delivery (has )?failed|message not delivered|tidak (dapat|bisa) dikirim|gagal (dikirim|terkirim))/iu', $subject);
    }

    public function isSystemSender(array $email): bool
    {
        return (bool) preg_match('/^(mailer-daemon|postmaster)@/i', trim((string) ($email['from'] ?? '')));
    }

    /**
     * Pesan asli dari laporan bounce: lewat Message-ID, atau alamat penerima yang disebut di isi laporan.
     */
    public function matchBounce(array $email): ?OutreachMessage
    {
        $ids = $this->messageIds($email);

        if ($ids && $message = OutreachMessage::where('type', 'email')->whereIn('message_id', $ids)->first()) {
            return $message;
        }

        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', (string) ($email['body'] ?? ''), $found);

        $ours = array_map('strtolower', array_filter([config('mail.from.address'), config('leadhunter.imap.username')]));
        $addresses = collect($found[0] ?? [])
            ->map(fn ($address) => strtolower($address))
            ->reject(fn ($address) => in_array($address, $ours, true) || preg_match('/^(mailer-daemon|postmaster)@/', $address))
            ->unique()
            ->values()
            ->all();

        if (! $addresses) {
            return null;
        }

        return OutreachMessage::where('type', 'email')
            ->where('status', 'sent')
            ->whereHas('lead', fn ($q) => $q->whereRaw('LOWER(email) IN ('.implode(',', array_fill(0, count($addresses), '?')).')', $addresses))
            ->latest('sent_at')
            ->first();
    }

    protected function bounce(array $email): void
    {
        $message = $this->matchBounce($email);

        if (! $message || $message->status !== 'sent') {
            return;
        }

        $message->loadMissing('lead');

        $message->markFailed('Bounce: email tidak sampai (alamat tidak ada atau kotak masuk menolak).');
        $message->update(['attempts' => (int) config('leadhunter.sending.max_attempts', 3)]);

        if ($address = $message->lead?->email) {
            BlacklistEntry::add('email', $address, 'bounce');
            OutreachMessage::cancelOpenFor($message->lead_id, "Email {$address} bounce, pesan email lain dibatalkan.", type: 'email');
        }

        $this->bounced++;
    }

    /**
     * Subjek "unsubscribe"/"berhenti"/"stop", atau balasan singkat yang isinya kata tersebut
     * (teks kutipan email kita di bawahnya diabaikan).
     */
    public function isUnsubscribeRequest(array $email): bool
    {
        $subject = preg_replace('/^\s*((re|fw|fwd|balas)\s*:\s*)+/iu', '', (string) ($email['subject'] ?? ''));

        if (preg_match('/^\s*(unsubscribe|berhenti|stop)\b/iu', $subject)) {
            return true;
        }

        $reply = $this->replyText((string) ($email['body'] ?? ''));

        return $reply !== '' && mb_strlen($reply) <= 200 && preg_match('/\b(unsubscribe|berhenti|stop)\b/iu', $reply) === 1;
    }

    /**
     * Ambil hanya balasan baru, sebelum kutipan pesan asli.
     */
    public function replyText(string $body): string
    {
        $reply = [];

        foreach (preg_split('/\r?\n/', $body) as $line) {
            if (str_starts_with(ltrim($line), '>') || preg_match('/(wrote|menulis)\s*:\s*$/iu', $line) || preg_match('/^-{2,}\s*(original|pesan asli)/iu', $line)) {
                break;
            }
            $reply[] = $line;
        }

        return trim(preg_replace('/[ \t]+/', ' ', implode("\n", $reply)));
    }

    protected function unsubscribe(OutreachMessage $message, ?string $from): void
    {
        $email = $from ?: $message->lead?->email;

        if (! $email) {
            return;
        }

        BlacklistEntry::add('email', $email, 'unsubscribe (balasan)');

        // Minta berhenti = berhenti di semua kanal, bukan hanya email.
        OutreachMessage::cancelOpenFor($message->lead_id, 'Penerima minta berhenti dihubungi.');

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
