<?php

namespace App\Jobs;

use App\Exceptions\AiException;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\OutreachMessage;
use App\Models\ScrapingNotification;
use App\Services\AiService;
use App\Services\MessageQualityGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generate satu pesan AI untuk satu lead di satu campaign.
 * Satu job per lead: request HTTP tidak timeout, dan error 429 cukup di-retry per lead.
 */
class GenerateOutreachJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [15, 60];

    public $timeout = 180;

    /**
     * @param  array  $context  mode (ai|hybrid), template_id, offer, sender, tone, language, instruction
     */
    public function __construct(
        public int $leadId,
        public int $campaignId,
        public string $channel,
        public array $context = [],
    ) {
    }

    public function handle(AiService $ai): void
    {
        $lead = Lead::find($this->leadId);
        $campaign = Campaign::find($this->campaignId);

        if (! $lead || ! $campaign) {
            $this->finish($campaign, false);

            return;
        }

        $alreadyExists = OutreachMessage::where('lead_id', $lead->id)
            ->where('campaign_id', $campaign->id)
            ->where('type', $this->channel)
            ->whereIn('status', ['pending', 'queued'])
            ->exists();

        if ($alreadyExists) {
            $this->finish($campaign, true);

            return;
        }

        try {
            $template = ! empty($this->context['template_id']) ? MessageTemplate::find($this->context['template_id']) : null;

            if (($this->context['mode'] ?? 'ai') === 'hybrid' && $template) {
                $extras = ['offer' => $this->context['offer'] ?? null, 'sender_name' => $this->context['sender'] ?? null];
                $draft = MessageTemplate::render($template->body, $lead, $extras);
                $result = [
                    'subject' => $this->channel === 'email' && $template->subject
                        ? MessageTemplate::render($template->subject, $lead, $extras)
                        : ($this->channel === 'email' ? $ai->defaultSubject($lead) : null),
                    'message' => $ai->polishDraft($lead, $draft, $this->context + ['tone' => $template->tone, 'language' => $template->language]),
                ];
                $result['needs_review'] = app(MessageQualityGate::class)->problems($result['message'], $result['subject'], $lead, $this->channel) !== [];
                $mode = 'hybrid';
            } else {
                $result = $ai->generateOutreach($lead, $this->channel, $this->context);
                $mode = 'ai';
            }
        } catch (AiException $e) {
            // Tanpa API key, retry tidak ada gunanya.
            if (! $ai->isConfigured()) {
                $this->fail($e);

                return;
            }

            throw $e;
        }

        OutreachMessage::create([
            'lead_id' => $lead->id,
            'campaign_id' => $campaign->id,
            'type' => $this->channel,
            'subject' => $result['subject'],
            'message' => $result['message'],
            'status' => 'pending',
            'mode' => $mode,
            'template_id' => $template?->id,
            'needs_review' => $result['needs_review'] ?? false,
            'prompt_variant' => $result['variant'] ?? null,
            'created_by' => $campaign->created_by,
        ]);

        $this->finish($campaign, true);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error("Failed to generate AI message for lead {$this->leadId}: ".$exception?->getMessage());

        $this->finish(Campaign::find($this->campaignId), false, $exception?->getMessage());
    }

    protected function finish(?Campaign $campaign, bool $success, ?string $error = null): void
    {
        if (! $campaign) {
            return;
        }

        $campaign->recordGeneration($success);
        $campaign->refresh();

        if ($campaign->generation_total > 0 && ! $campaign->isGenerating()) {
            $failed = $campaign->generation_failed;
            ScrapingNotification::notify(
                $failed > 0 && $campaign->generation_done === 0 ? 'failed' : 'success',
                'Generate Pesan Selesai',
                "Campaign \"{$campaign->name}\": {$campaign->generation_done} pesan berhasil dibuat".($failed ? ", {$failed} gagal".($error ? " ({$error})" : '') : '').'.',
                $campaign->niche,
                $campaign->location
            );
        }
    }
}
