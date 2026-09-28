<?php

namespace App\Services;

use App\Jobs\GenerateOutreachJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\OutreachMessage;
use Illuminate\Support\Collection;

/**
 * Membuat pesan outreach untuk sekumpulan lead di satu campaign.
 * Mode template dirender langsung (instan, tanpa biaya AI); mode AI/hybrid diantrekan per lead.
 */
class OutreachGenerator
{
    /**
     * @param  Collection<int, Lead>  $leads
     * @param  array<int, string>  $channels  email|whatsapp
     * @param  string  $mode  template|ai|hybrid (ai_generate = ai)
     * @param  array  $context  offer, sender, tone, language, instruction
     * @return array{created: int, queued: int, skipped: int}
     */
    public function generate(Campaign $campaign, Collection $leads, array $channels, string $mode, ?MessageTemplate $template = null, array $context = []): array
    {
        $mode = $mode === 'ai_generate' ? 'ai' : $mode;
        $created = 0;
        $skipped = 0;
        $jobs = [];

        foreach ($leads as $lead) {
            foreach ($channels as $channel) {
                if ($this->hasOpenMessage($lead, $campaign, $channel)
                    || ($channel === 'whatsapp' && empty($lead->whatsapp_number))
                    || ($channel === 'email' && empty($lead->email))) {
                    $skipped++;

                    continue;
                }

                if ($mode === 'template' && $template) {
                    $this->createFromTemplate($lead, $campaign, $channel, $template, $context);
                    $created++;
                } else {
                    $jobs[] = new GenerateOutreachJob($lead->id, $campaign->id, $channel, $context + [
                        'mode' => $mode === 'hybrid' ? 'hybrid' : 'ai',
                        'template_id' => $template?->id,
                        'tone' => $context['tone'] ?? $template?->tone,
                        'language' => $context['language'] ?? $template?->language,
                    ]);
                }
            }
        }

        // Total dicatat sebelum dispatch: di queue "sync" (test) job langsung selesai saat di-dispatch.
        $campaign->queueGeneration(count($jobs));

        foreach ($jobs as $job) {
            dispatch($job);
        }

        return ['created' => $created, 'queued' => count($jobs), 'skipped' => $skipped];
    }

    public function createFromTemplate(Lead $lead, Campaign $campaign, string $channel, MessageTemplate $template, array $context = []): OutreachMessage
    {
        $extras = ['offer' => $context['offer'] ?? null, 'sender_name' => $context['sender'] ?? null];

        return OutreachMessage::create([
            'lead_id' => $lead->id,
            'campaign_id' => $campaign->id,
            'type' => $channel,
            'subject' => $channel === 'email' && $template->subject ? MessageTemplate::render($template->subject, $lead, $extras) : null,
            'message' => MessageTemplate::render($template->body, $lead, $extras),
            'status' => 'pending',
            'mode' => 'template',
            'template_id' => $template->id,
        ]);
    }

    /**
     * Draft yang belum terkirim (pending/queued) untuk lead + campaign + channel yang sama.
     */
    public function hasOpenMessage(Lead $lead, Campaign $campaign, string $channel): bool
    {
        return OutreachMessage::where('lead_id', $lead->id)
            ->where('campaign_id', $campaign->id)
            ->where('type', $channel)
            ->whereIn('status', ['pending', 'queued'])
            ->exists();
    }
}
