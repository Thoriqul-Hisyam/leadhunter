<?php

namespace App\Services;

use App\Exceptions\OutreachSendException;
use App\Helpers\Phone;
use App\Jobs\SendOutreachJob;
use App\Mail\OutreachMail;
use App\Models\BlacklistEntry;
use App\Models\OutreachMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class OutreachSender
{
    /**
     * Kirim satu email outreach sekarang juga.
     *
     * @throws OutreachSendException
     */
    public function sendEmail(OutreachMessage $message): void
    {
        $message->loadMissing('lead');
        $lead = $message->lead;

        if ($message->type !== 'email') {
            throw new OutreachSendException('Pesan ini bukan email.');
        }

        if ($message->isDelivered()) {
            throw new OutreachSendException('Pesan ini sudah terkirim sebelumnya dan tidak akan dikirim ulang.');
        }

        if (! $lead || empty($lead->email)) {
            $this->failPermanently($message, 'Lead tidak memiliki alamat email.');
        }

        if (BlacklistEntry::blocks($lead, 'email')) {
            $this->failPermanently($message, "Email {$lead->email} ada di blacklist (unsubscribe). Pesan tidak dikirim.");
        }

        $messageId = $message->message_id ?: Str::uuid().'@'.$this->messageIdDomain();

        $message->update([
            'attempts' => $message->attempts + 1,
            'message_id' => $messageId,
        ]);

        try {
            Mail::to($lead->email)->send(new OutreachMail(
                $message->subject ?: 'Outreach Mail',
                $message->message,
                $this->unsubscribeUrl($message),
                $messageId,
                $this->unsubscribeMailto(),
            ));
        } catch (Throwable $e) {
            $message->markFailed($e->getMessage());

            throw new OutreachSendException('Gagal mengirim email: '.$e->getMessage(), 0, $e);
        }

        $message->markSent();
    }

    /**
     * Link wa.me untuk pesan WhatsApp (dikirim manual oleh pengguna lewat WhatsApp).
     *
     * @throws OutreachSendException
     */
    public function whatsAppUrl(OutreachMessage $message): string
    {
        $message->loadMissing('lead');
        $lead = $message->lead;

        if (BlacklistEntry::blocks($lead, 'whatsapp')) {
            throw new OutreachSendException("Nomor {$lead->phone} ada di blacklist. Pesan tidak dikirim.");
        }

        $url = Phone::whatsAppUrl($lead->phone, $message->message);

        if (! $url) {
            throw new OutreachSendException("{$lead->business_name} tidak memiliki nomor WhatsApp yang valid.");
        }

        return $url;
    }

    /**
     * True jika mailer hanya menulis ke log (email tidak benar-benar terkirim).
     */
    public function isFakeMailer(): bool
    {
        return in_array(config('mail.default'), ['log', 'array'], true);
    }

    /**
     * Link unsubscribe bertanda tangan. Null jika APP_URL bukan alamat publik
     * (misalnya localhost saat app dijalankan lokal): link seperti itu tidak bisa dibuka penerima.
     */
    public function unsubscribeUrl(OutreachMessage $message): ?string
    {
        if (! $this->appIsPublic()) {
            return null;
        }

        return URL::signedRoute('unsubscribe', ['outreachMessage' => $message->id]);
    }

    /**
     * Alternatif tanpa server publik: penerima membalas dengan subjek "unsubscribe"
     * (dideteksi otomatis oleh ReplyDetector jika IMAP aktif).
     */
    public function unsubscribeMailto(): ?string
    {
        $from = config('mail.from.address');

        return $from ? 'mailto:'.$from.'?subject=unsubscribe' : null;
    }

    public function appIsPublic(): bool
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            return false;
        }

        foreach (['.test', '.local', '.localhost', '.internal'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        return ! filter_var($host, FILTER_VALIDATE_IP)
            || (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /*
    |--------------------------------------------------------------------------
    | Antrean kirim (bulk send)
    |--------------------------------------------------------------------------
    */

    /**
     * Masukkan email ke antrean kirim dengan jeda acak antar email.
     *
     * @param  Collection<int, OutreachMessage>  $messages
     * @return array{queued: int, skipped: int, last_at: ?\Illuminate\Support\Carbon}
     */
    public function queue(Collection $messages): array
    {
        $min = (int) config('leadhunter.sending.min_gap_seconds', 90);
        $max = max($min, (int) config('leadhunter.sending.max_gap_seconds', 240));

        // Sambung di belakang antrean yang sudah ada.
        $lastScheduled = OutreachMessage::where('status', 'queued')->max('scheduled_at');
        $at = $lastScheduled && now()->lt($lastScheduled) ? Carbon::parse($lastScheduled) : now();

        $queued = 0;
        $skipped = 0;
        $first = true;

        foreach ($messages as $message) {
            $message->loadMissing('lead');

            if ($message->type !== 'email' || ! in_array($message->status, ['pending', 'failed'], true) || empty($message->lead?->email)) {
                $skipped++;

                continue;
            }

            if (! ($first && ! $lastScheduled)) {
                $at = $at->copy()->addSeconds(random_int($min, $max));
            }
            $first = false;

            $message->update(['status' => 'queued', 'scheduled_at' => $at, 'last_error' => null]);
            $queued++;
        }

        return ['queued' => $queued, 'skipped' => $skipped, 'last_at' => $queued ? $at : null];
    }

    /**
     * Dipanggil scheduler tiap menit: kirim email antrean yang sudah jatuh tempo,
     * tanpa melewati batas per jam.
     */
    public function dispatchDue(): int
    {
        $limit = (int) config('leadhunter.sending.hourly_limit', 20);
        $sentLastHour = OutreachMessage::where('type', 'email')
            ->whereIn('status', ['sent', 'replied'])
            ->where('sent_at', '>=', now()->subHour())
            ->count();

        // Pesan yang sedang diproses worker (scheduled_at sudah dikosongkan) ikut dihitung.
        $inFlight = OutreachMessage::where('status', 'queued')->whereNull('scheduled_at')->count();

        $budget = $limit - $sentLastHour - $inFlight;

        if ($budget <= 0) {
            return 0;
        }

        $due = OutreachMessage::where('status', 'queued')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($budget)
            ->get();

        foreach ($due as $message) {
            // scheduled_at = null menandai "sudah diserahkan ke worker", agar tidak di-dispatch dua kali.
            $message->update(['scheduled_at' => null]);
            SendOutreachJob::dispatch($message->id);
        }

        return $due->count();
    }

    /**
     * Jadwalkan ulang email gagal yang masih mungkin berhasil, dan pesan yang tersangkut di worker.
     */
    public function retryFailed(): int
    {
        $maxAttempts = (int) config('leadhunter.sending.max_attempts', 3);

        $stuck = OutreachMessage::where('status', 'queued')
            ->whereNull('scheduled_at')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update(['scheduled_at' => now()]);

        $retry = OutreachMessage::where('type', 'email')
            ->where('status', 'failed')
            ->where('attempts', '>', 0)
            ->where('attempts', '<', $maxAttempts)
            ->where('updated_at', '<', now()->subMinutes(30))
            ->get();

        return $stuck + $this->queue($retry)['queued'];
    }

    /**
     * @throws OutreachSendException
     */
    protected function failPermanently(OutreachMessage $message, string $reason): never
    {
        $message->markFailed($reason);
        // attempts = batas maksimum → tidak ikut di-retry otomatis.
        $message->update(['attempts' => (int) config('leadhunter.sending.max_attempts', 3)]);

        throw new OutreachSendException($reason);
    }

    protected function messageIdDomain(): string
    {
        $from = (string) config('mail.from.address');

        return Str::contains($from, '@') ? Str::after($from, '@') : (parse_url(config('app.url'), PHP_URL_HOST) ?: 'leadhunter.local');
    }
}
