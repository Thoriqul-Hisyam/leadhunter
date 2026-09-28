<?php

namespace App\Services;

use App\Exceptions\AiException;
use App\Models\BlacklistEntry;
use App\Models\CampaignStep;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sequence follow-up: langkah berikutnya dibuat untuk pesan terkirim yang belum dibalas setelah N hari.
 * - Campaign dengan langkah sendiri (campaign_steps): hingga 4 langkah lanjutan, boleh lintas kanal.
 * - Campaign tanpa langkah: satu follow-up default dari Pengaturan (jika diaktifkan).
 * Sequence berhenti saat lead membalas, minta berhenti, atau stage-nya sudah ditutup.
 */
class FollowupService
{
    public function __construct(protected AiService $ai, protected OutreachSender $sender, protected MessageQualityGate $gate)
    {
    }

    /**
     * Pesan terkirim yang belum punya langkah lanjutan dan sudah cukup lama.
     */
    public function candidates(int $days = 1): Builder
    {
        return OutreachMessage::query()
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', now()->subDays(max(1, $days)))
            ->where('sent_at', '>=', now()->subDays(180))
            ->where('step', '<', CampaignStep::MAX_STEP)
            ->whereDoesntHave('followups')
            ->whereHas('lead', fn ($q) => $q->whereNotIn('pipeline_stage', Lead::SEQUENCE_STOP_STAGES));
    }

    /**
     * Langkah setelah pesan ini, atau null jika sequence-nya sudah selesai.
     *
     * @return array{step: int, channel: string, delay_days: int, auto_queue: bool, final: bool}|null
     */
    public function nextStep(OutreachMessage $message): ?array
    {
        $current = max(1, (int) $message->step);
        $steps = $message->campaign?->steps ?? collect();

        if ($steps->isNotEmpty()) {
            $next = $steps->firstWhere('step', $current + 1);

            return $next ? [
                'step' => $next->step,
                'channel' => $next->channel,
                'delay_days' => $next->delay_days,
                'auto_queue' => $next->auto_queue,
                'final' => $next->step >= 3 && $next->step === $steps->max('step'),
            ] : null;
        }

        if ($current === 1 && ! $message->followup_of_id && Setting::followupEnabled()) {
            return ['step' => 2, 'channel' => 'same', 'delay_days' => Setting::followupDays(), 'auto_queue' => false, 'final' => false];
        }

        return null;
    }

    /**
     * @return int jumlah langkah lanjutan yang dibuat
     */
    public function generateDue(int $limit = 20): int
    {
        $created = 0;
        $toQueue = collect();

        // lazyById: pesan yang sudah diproses keluar dari hasil query tanpa membuat baris lain terlewat.
        foreach ($this->candidates()->with(['lead', 'campaign.steps'])->lazyById(100) as $previous) {
            if ($created >= $limit) {
                break;
            }

            $step = $this->nextStep($previous);
            $lead = $previous->lead;

            if (! $step || ! $lead || $previous->sent_at->gt(now()->subDays($step['delay_days']))) {
                continue;
            }

            if ($lead->sequenceStopReason()) {
                continue;
            }

            $channel = $this->resolveChannel($step['channel'], $previous, $lead);

            if (! $channel) {
                continue;
            }

            try {
                $text = $this->ai->generateFollowup($lead, $previous, [], $channel, $step['final']);
                $mode = 'ai';
            } catch (AiException) {
                $text = $this->fallbackText($lead->business_name, $step['final']);
                $mode = 'template';
            }

            $subject = $channel === 'email' ? $this->subject($previous, $lead) : null;

            $followup = OutreachMessage::create([
                'lead_id' => $lead->id,
                'campaign_id' => $previous->campaign_id,
                'step' => $step['step'],
                'type' => $channel,
                'subject' => $subject,
                'message' => $text,
                'status' => 'pending',
                'mode' => $mode,
                // Pesan yang tidak lolos quality gate tidak ikut diantrekan otomatis.
                'needs_review' => $mode === 'ai' && $this->gate->problems($text, $subject, $lead, $channel) !== [],
                'followup_of_id' => $previous->id,
                'created_by' => $previous->created_by,
            ]);

            if ($step['auto_queue']) {
                $toQueue->push($followup);
            }

            $created++;
        }

        $queued = $toQueue->isNotEmpty() ? $this->sender->queue($toQueue)['queued'] : 0;

        if ($created > 0) {
            $review = $created - $queued;
            ScrapingNotification::notify(
                'success',
                'Follow-up Dibuat',
                "{$created} langkah follow-up dibuat untuk lead yang belum membalas"
                    .($queued ? ", {$queued} langsung masuk antrean kirim" : '')
                    .($review ? ", {$review} menunggu review di halaman Outreach (status Draft)" : '').'.'
            );
        }

        return $created;
    }

    /**
     * Kanal langkah ini; jika lead tidak bisa dihubungi di kanal tersebut, pakai kanal pesan sebelumnya.
     */
    public function resolveChannel(string $wanted, OutreachMessage $previous, Lead $lead): ?string
    {
        $options = array_unique([$wanted === 'same' ? $previous->type : $wanted, $previous->type]);

        foreach ($options as $channel) {
            $reachable = match ($channel) {
                'email' => ! empty($lead->email),
                'whatsapp' => $this->sender->canWhatsApp($lead),
                default => false,
            };

            if ($reachable && ! BlacklistEntry::blocks($lead, $channel)) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * "Re: <subjek email pertama di campaign ini>" agar masuk ke thread yang sama di inbox lead.
     */
    protected function subject(OutreachMessage $previous, Lead $lead): string
    {
        $original = OutreachMessage::where('lead_id', $lead->id)
            ->where('campaign_id', $previous->campaign_id)
            ->where('type', 'email')
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->orderBy('step')
            ->orderBy('id')
            ->value('subject');

        $base = preg_replace('/^\s*(re:\s*)+/i', '', (string) $original) ?: $this->ai->defaultSubject($lead);

        return 'Re: '.$base;
    }

    protected function fallbackText(string $businessName, bool $final = false): string
    {
        $sender = Setting::senderIdentity();
        $offer = Setting::defaultOffer();

        return $final
            ? "Halo {$businessName},\n\nIni pesan terakhir dari saya soal {$offer}, saya tidak akan mengganggu lagi. Kalau suatu saat butuh bantuan, silakan balas pesan ini kapan saja.\n\nSalam,\n{$sender}"
            : "Halo {$businessName},\n\nSaya ingin menindaklanjuti pesan saya sebelumnya mengenai {$offer}. Apakah ada waktu untuk diskusi singkat minggu ini?\n\nSalam,\n{$sender}";
    }
}
