<?php

namespace App\Services;

use App\Exceptions\AiException;
use App\Models\BlacklistEntry;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;

/**
 * Follow-up otomatis: satu pesan follow-up (status pending, untuk direview) bagi pesan
 * yang belum dibalas setelah N hari.
 */
class FollowupService
{
    public function __construct(protected AiService $ai)
    {
    }

    public function candidates(int $days): Builder
    {
        return OutreachMessage::query()
            ->where('status', 'sent')
            ->whereNull('followup_of_id')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', now()->subDays($days))
            ->whereDoesntHave('followups')
            ->whereHas('lead', fn ($q) => $q->whereNotIn('pipeline_stage', ['replied', 'meeting', 'deal', 'lost']));
    }

    /**
     * @return int jumlah follow-up yang dibuat
     */
    public function generateDue(int $limit = 20): int
    {
        if (! Setting::followupEnabled()) {
            return 0;
        }

        $created = 0;

        foreach ($this->candidates(Setting::followupDays())->with('lead')->orderBy('sent_at')->limit($limit)->get() as $original) {
            $lead = $original->lead;

            // Lead sudah membalas di pesan lain, atau minta berhenti dihubungi.
            if (OutreachMessage::where('lead_id', $lead->id)->where('status', 'replied')->exists()
                || BlacklistEntry::blocks($lead, $original->type)) {
                continue;
            }

            try {
                $text = $this->ai->generateFollowup($lead, $original);
                $mode = 'ai';
            } catch (AiException) {
                $text = $this->fallbackText($lead->business_name);
                $mode = 'template';
            }

            OutreachMessage::create([
                'lead_id' => $lead->id,
                'campaign_id' => $original->campaign_id,
                'type' => $original->type,
                'subject' => $original->type === 'email' ? 'Re: '.($original->subject ?: $this->ai->defaultSubject($lead)) : null,
                'message' => $text,
                'status' => 'pending',
                'mode' => $mode,
                'followup_of_id' => $original->id,
            ]);

            $created++;
        }

        if ($created > 0) {
            ScrapingNotification::notify(
                'success',
                'Follow-up Siap Direview',
                "{$created} pesan follow-up dibuat untuk lead yang belum membalas. Cek halaman Outreach (status Pending) sebelum mengirim."
            );
        }

        return $created;
    }

    protected function fallbackText(string $businessName): string
    {
        $sender = Setting::senderIdentity();
        $offer = Setting::defaultOffer();

        return "Halo {$businessName},\n\nSaya ingin menindaklanjuti pesan saya sebelumnya mengenai {$offer}. Apakah ada waktu untuk diskusi singkat minggu ini?\n\nSalam,\n{$sender}";
    }
}
