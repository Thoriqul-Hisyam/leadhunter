<?php

namespace App\Services;

use App\Exceptions\OutreachSendException;
use App\Helpers\Phone;
use App\Jobs\SendOutreachJob;
use App\Mail\OutreachMail;
use App\Models\BlacklistEntry;
use App\Models\OutreachMessage;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class OutreachSender
{
    public const CHANNELS = ['email', 'whatsapp'];

    public function __construct(protected WhatsAppManager $whatsapp)
    {
    }

    /**
     * Kirim satu pesan sekarang juga, sesuai channel-nya.
     *
     * @throws OutreachSendException
     */
    public function send(OutreachMessage $message): void
    {
        $message->type === 'whatsapp' ? $this->sendWhatsApp($message) : $this->sendEmail($message);
    }

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

        $this->guardSequence($message);

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
     * Kirim satu pesan WhatsApp lewat gateway (Fonnte/Wablas).
     *
     * @throws OutreachSendException
     */
    public function sendWhatsApp(OutreachMessage $message): void
    {
        $message->loadMissing('lead');
        $lead = $message->lead;
        $gateway = $this->whatsapp->driver();

        if ($message->type !== 'whatsapp') {
            throw new OutreachSendException('Pesan ini bukan WhatsApp.');
        }

        if ($message->isDelivered()) {
            throw new OutreachSendException('Pesan ini sudah terkirim sebelumnya dan tidak akan dikirim ulang.');
        }

        if (! $gateway->isAutomatic()) {
            throw new OutreachSendException('WhatsApp gateway belum diatur (mode manual). Kirim lewat tombol WhatsApp, atau pilih Fonnte/Wablas di Pengaturan.');
        }

        $this->guardSequence($message);

        if (! $lead || ! $this->canWhatsApp($lead)) {
            $this->failPermanently($message, $lead?->whatsapp_number
                ? "Nomor {$lead->phone} adalah telepon kantor, bukan nomor seluler. Izinkan nomor kantor di Pengaturan jika bisnis ini memakai WhatsApp Business di nomor tersebut."
                : 'Lead tidak memiliki nomor WhatsApp yang valid.');
        }

        if (BlacklistEntry::blocks($lead, 'whatsapp')) {
            $this->failPermanently($message, "Nomor {$lead->phone} ada di blacklist. Pesan tidak dikirim.");
        }

        // Melindungi nomor pengirim: satu pesan pembuka per lead per campaign (follow-up/sequence dikecualikan).
        if (! $message->followup_of_id && ($message->step ?? 1) <= 1) {
            $alreadyContacted = OutreachMessage::where('lead_id', $lead->id)
                ->where('campaign_id', $message->campaign_id)
                ->where('type', 'whatsapp')
                ->whereIn('status', ['sent', 'replied'])
                ->whereKeyNot($message->id)
                ->exists();

            if ($alreadyContacted) {
                $this->failPermanently($message, 'Lead ini sudah menerima WhatsApp di campaign yang sama.');
            }
        }

        $message->update(['attempts' => $message->attempts + 1]);

        $result = $gateway->send($lead->whatsapp_number, $message->message);

        if (! $result->ok) {
            if ($result->permanent) {
                $this->failPermanently($message, $result->error);
            }

            $message->markFailed((string) $result->error);

            throw new OutreachSendException((string) $result->error);
        }

        $message->update(['message_id' => $result->id ?: null]);
        $message->markSent();
    }

    /**
     * Lead bisa dikirimi WhatsApp otomatis: nomor valid, dan seluler (kecuali nomor kantor diizinkan).
     */
    public function canWhatsApp($lead): bool
    {
        return ! empty($lead->whatsapp_number)
            && ($lead->phone_is_mobile || config('leadhunter.whatsapp.allow_landline'));
    }

    public function whatsAppGatewayActive(): bool
    {
        return $this->whatsapp->isAutomatic();
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
     * Batas & jeda per channel.
     *
     * @return array{hourly: int, daily: int, min_gap: int, max_gap: int}
     */
    public function limits(string $channel): array
    {
        $config = $channel === 'whatsapp' ? config('leadhunter.whatsapp') : config('leadhunter.sending');
        $min = (int) ($config['min_gap_seconds'] ?? 90);

        return [
            'hourly' => (int) ($config['hourly_limit'] ?? 20),
            'daily' => (int) ($config['daily_limit'] ?? 80),
            'min_gap' => $min,
            'max_gap' => max($min, (int) ($config['max_gap_seconds'] ?? 240)),
        ];
    }

    /**
     * Masukkan pesan (email, dan WhatsApp jika gateway aktif) ke antrean kirim dengan jeda acak.
     * Jadwal otomatis digeser ke dalam jendela kirim (jam & hari kerja).
     *
     * @param  Collection<int, OutreachMessage>  $messages
     * @return array{queued: int, skipped: int, last_at: ?Carbon, by_channel: array<string, int>}
     */
    public function queue(Collection $messages): array
    {
        $gatewayActive = $this->whatsAppGatewayActive();
        $cursor = [];
        $byChannel = array_fill_keys(self::CHANNELS, 0);
        $skipped = 0;
        $lastAt = null;

        foreach ($messages as $message) {
            $message->loadMissing('lead');
            $channel = $message->type === 'whatsapp' ? 'whatsapp' : 'email';

            // Pesan yang ditandai "perlu review" oleh quality gate harus diedit/disetujui dulu.
            $eligible = in_array($message->status, ['pending', 'failed'], true) && ! $message->needs_review && match ($channel) {
                'email' => ! empty($message->lead?->email),
                'whatsapp' => $gatewayActive && $message->lead && $this->canWhatsApp($message->lead),
            };

            if (! $eligible) {
                $skipped++;

                continue;
            }

            $limits = $this->limits($channel);

            if (! isset($cursor[$channel])) {
                // Sambung di belakang antrean channel yang sama yang sudah ada.
                $last = OutreachMessage::where('status', 'queued')->where('type', $channel)->max('scheduled_at');
                $cursor[$channel] = $last && now()->lt($last)
                    ? Carbon::parse($last)->addSeconds(random_int($limits['min_gap'], $limits['max_gap']))
                    : now();
            } else {
                $cursor[$channel] = $cursor[$channel]->copy()->addSeconds(random_int($limits['min_gap'], $limits['max_gap']));
            }

            $cursor[$channel] = $this->nextSendableTime($cursor[$channel]);

            $message->update(['status' => 'queued', 'scheduled_at' => $cursor[$channel], 'last_error' => null]);
            $byChannel[$channel]++;
            $lastAt = $lastAt === null || $cursor[$channel]->gt($lastAt) ? $cursor[$channel] : $lastAt;
        }

        return [
            'queued' => array_sum($byChannel),
            'skipped' => $skipped,
            'last_at' => $lastAt,
            'by_channel' => $byChannel,
        ];
    }

    /**
     * Dipanggil scheduler tiap menit: kirim pesan antrean yang jatuh tempo, per channel,
     * tanpa melewati batas per jam/per hari, hanya di dalam jendela kirim, dan maksimal
     * satu pesan per channel per putaran (antrean yang tertunda tidak terkirim sekaligus).
     *
     * @return int jumlah pesan yang diserahkan ke worker
     */
    public function dispatchDue(): int
    {
        if (! $this->withinWindow(now())) {
            return 0;
        }

        $dispatched = 0;

        foreach (self::CHANNELS as $channel) {
            if ($channel === 'whatsapp' && ! $this->whatsAppGatewayActive()) {
                continue;
            }

            if ($this->remainingBudget($channel) <= 0) {
                continue;
            }

            $message = OutreachMessage::where('status', 'queued')
                ->where('type', $channel)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->orderBy('scheduled_at')
                ->first();

            if (! $message) {
                continue;
            }

            // scheduled_at = null menandai "sudah diserahkan ke worker", agar tidak di-dispatch dua kali.
            $message->update(['scheduled_at' => null]);
            SendOutreachJob::dispatch($message->id);
            $dispatched++;
        }

        return $dispatched;
    }

    public function remainingBudget(string $channel): int
    {
        $limits = $this->limits($channel);
        $sent = fn (Carbon $since) => OutreachMessage::where('type', $channel)
            ->whereIn('status', ['sent', 'replied'])
            ->where('sent_at', '>=', $since)
            ->count();

        // Pesan yang sedang diproses worker ikut dihitung.
        $inFlight = OutreachMessage::where('status', 'queued')->where('type', $channel)->whereNull('scheduled_at')->count();

        return min(
            $limits['hourly'] - $sent(now()->subHour()),
            $limits['daily'] - $sent(now()->startOfDay()),
        ) - $inFlight;
    }

    public function withinWindow(Carbon $at): bool
    {
        return $this->nextSendableTime($at)->equalTo($at);
    }

    /**
     * Waktu terdekat (>= $at) yang masuk jendela kirim: jam kerja, dan hari kerja jika diaktifkan.
     */
    public function nextSendableTime(Carbon $at): Carbon
    {
        $at = $at->copy();
        [$startHour, $startMinute] = array_map('intval', explode(':', (string) config('leadhunter.sending.window_start', '08:00')) + [1 => 0]);
        [$endHour, $endMinute] = array_map('intval', explode(':', (string) config('leadhunter.sending.window_end', '16:00')) + [1 => 0]);
        $weekdaysOnly = (bool) config('leadhunter.sending.weekdays_only', true);

        for ($i = 0; $i < 8; $i++) {
            $start = $at->copy()->setTime($startHour, $startMinute);
            $end = $at->copy()->setTime($endHour, $endMinute);

            if ($weekdaysOnly && $at->isWeekend()) {
                $at = $at->copy()->addDay()->setTime($startHour, $startMinute);

                continue;
            }

            if ($at->lt($start)) {
                return $start;
            }

            if ($at->lt($end)) {
                return $at;
            }

            $at = $at->copy()->addDay()->setTime($startHour, $startMinute);
        }

        return $at;
    }

    /**
     * Jadwalkan ulang pesan gagal yang masih mungkin berhasil, dan pesan yang tersangkut di worker.
     */
    public function retryFailed(): int
    {
        $maxAttempts = (int) config('leadhunter.sending.max_attempts', 3);

        $stuck = OutreachMessage::where('status', 'queued')
            ->whereNull('scheduled_at')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update(['scheduled_at' => now()]);

        $retry = OutreachMessage::whereIn('type', self::CHANNELS)
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
    /**
     * Langkah follow-up tidak pernah dikirim ke lead yang sudah membalas atau sudah ditutup,
     * walaupun pesannya sudah terlanjur dibuat / masuk antrean.
     *
     * @throws OutreachSendException
     */
    protected function guardSequence(OutreachMessage $message): void
    {
        if ($message->isSequenceStep() && ($reason = $message->lead?->sequenceStopReason())) {
            $this->failPermanently($message, $reason);
        }
    }

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
