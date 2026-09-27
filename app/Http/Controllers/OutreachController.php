<?php

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Exceptions\OutreachSendException;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Models\OutreachMessage;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\OutreachGenerator;
use App\Services\OutreachSender;
use Illuminate\Http\Request;

class OutreachController extends Controller
{
    public function index(Request $request)
    {
        $query = OutreachMessage::with(['lead', 'campaign'])->latest();

        if ($request->filled('status') && in_array($request->status, OutreachMessage::STATUSES, true)) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type') && in_array($request->type, ['email', 'whatsapp'], true)) {
            $query->where('type', $request->type);
        }
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $messages = $query->paginate(20)->withQueryString();
        $campaigns = Campaign::all();
        $leads = Lead::all();
        $senderName = Setting::senderIdentity();
        $defaultOffer = Setting::defaultOffer();
        $queuedCount = OutreachMessage::where('status', 'queued')->count();
        $fakeMailer = app(OutreachSender::class)->isFakeMailer();

        return view('outreach.index', compact('messages', 'campaigns', 'leads', 'senderName', 'defaultOffer', 'queuedCount', 'fakeMailer'));
    }

    /**
     * Bulk generate dari tabel Leads. Setiap lead diproses oleh job terpisah,
     * jadi satu kegagalan AI tidak menghentikan lead lainnya.
     */
    public function generate(Request $request, OutreachGenerator $generator)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'type' => 'nullable|in:email,whatsapp',
        ]);

        $campaign = Campaign::findOrFail($request->campaign_id);
        $type = $request->input('type', 'email');

        $result = $generator->generate($campaign, Lead::whereIn('id', $request->lead_ids)->get(), [$type], 'ai');

        $message = "{$result['queued']} pesan ".ucfirst($type).' sedang digenerate AI di background.';
        if ($result['skipped']) {
            $message .= " {$result['skipped']} lead dilewati karena sudah punya draft {$type} di campaign ini.";
        }

        return redirect()->route('campaigns.show', $campaign)->with('success', $message);
    }

    public function regenerate(Request $request, OutreachMessage $outreachMessage, AiService $ai)
    {
        $request->validate([
            'custom_prompt' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $ai->generateOutreach($outreachMessage->lead, $outreachMessage->type, [
                'instruction' => $request->input('custom_prompt'),
            ]);

            return response()->json([
                'status' => 'success',
                'subject' => $outreachMessage->subject ?: $result['subject'],
                'message' => $result['message'],
            ]);
        } catch (AiException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal generate ulang pesan AI: '.$e->getMessage(),
            ], 500);
        }
    }

    public function send(Request $request, OutreachMessage $outreachMessage, OutreachSender $sender)
    {
        if ($outreachMessage->type === 'whatsapp') {
            try {
                $url = $sender->whatsAppUrl($outreachMessage);
            } catch (OutreachSendException $e) {
                return $this->respond($request, false, $e->getMessage(), 422);
            }

            if (! $outreachMessage->isDelivered()) {
                $outreachMessage->markSent();
            }

            return $request->expectsJson()
                ? response()->json(['status' => 'success', 'url' => $url])
                : redirect()->away($url);
        }

        try {
            $sender->sendEmail($outreachMessage);
        } catch (OutreachSendException $e) {
            return $this->respond($request, false, $e->getMessage(), $outreachMessage->isDelivered() ? 422 : 500);
        }

        $message = $sender->isFakeMailer()
            ? 'Email diproses, TETAPI MAIL_MAILER='.config('mail.default').' sehingga email hanya ditulis ke log, tidak benar-benar terkirim.'
            : 'Email outreach berhasil dikirim!';

        return $this->respond($request, true, $message);
    }

    public function updateStatus(Request $request, OutreachMessage $outreachMessage)
    {
        $request->validate([
            'status' => 'required|in:'.implode(',', OutreachMessage::STATUSES),
        ]);

        $outreachMessage->setStatus($request->status);

        return redirect()->back()->with('success', 'Status outreach berhasil diperbarui.');
    }

    public function update(Request $request, OutreachMessage $outreachMessage)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($outreachMessage->isDelivered()) {
            return redirect()->back()->with('error', 'Pesan yang sudah terkirim tidak bisa diedit.');
        }

        $outreachMessage->update([
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return redirect()->back()->with('success', 'Pesan outreach berhasil diperbarui.');
    }

    public function destroy(OutreachMessage $outreachMessage)
    {
        $outreachMessage->delete();

        return redirect()->back()->with('success', 'Pesan outreach berhasil dihapus.');
    }

    public function bulk(Request $request, OutreachSender $sender)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:outreach_messages,id',
            'action' => 'required|in:delete,send_queue,status_pending,status_sent,status_replied,status_failed',
        ]);

        $messages = OutreachMessage::with('lead')->whereIn('id', $request->ids)->get();

        if ($request->action === 'delete') {
            OutreachMessage::whereIn('id', $request->ids)->delete();

            return redirect()->back()->with('success', count($request->ids).' pesan outreach dihapus.');
        }

        if ($request->action === 'send_queue') {
            $result = $sender->queue($messages);
            $limit = config('leadhunter.sending.hourly_limit');
            $message = "{$result['queued']} email masuk antrean kirim (maks. {$limit}/jam, dengan jeda acak).";
            if ($result['last_at']) {
                $message .= ' Perkiraan selesai: '.$result['last_at']->format('d M H:i').'.';
            }
            if ($result['skipped']) {
                $message .= " {$result['skipped']} dilewati (bukan email, sudah terkirim, atau lead tanpa email).";
            }

            return redirect()->back()->with('success', $message);
        }

        $status = str_replace('status_', '', $request->action);
        $messages->each->setStatus($status);

        return redirect()->back()->with('success', $messages->count().' pesan ditandai sebagai '.ucfirst($status).'.');
    }

    public function getTemplates(Request $request)
    {
        $request->validate([
            'channel' => 'nullable|in:email,whatsapp',
            'niche' => 'nullable|string',
        ]);

        $query = MessageTemplate::active();

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('niche')) {
            $query->where('niche', 'like', '%'.$request->niche.'%');
        }

        return response()->json([
            'status' => 'success',
            'templates' => $query->get(['id', 'name', 'niche', 'channel', 'subject', 'body', 'tone']),
        ]);
    }

    public function composePreview(Request $request, AiService $ai)
    {
        $request->validate([
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'mode' => 'required|in:template,ai,hybrid',
            'template_id' => 'nullable|required_if:mode,template,hybrid|exists:message_templates,id',
            'type' => 'required|in:email,whatsapp',
            'offer' => 'nullable|string|max:255',
            'sender_name' => 'nullable|string|max:255',
        ]);

        $mode = $request->mode;
        $type = $request->type;
        $offer = $request->input('offer') ?: Setting::defaultOffer();
        $senderName = $request->input('sender_name') ?: Setting::senderIdentity();
        $extras = ['offer' => $offer, 'sender_name' => $senderName];

        $template = in_array($mode, ['template', 'hybrid']) && $request->template_id
            ? MessageTemplate::findOrFail($request->template_id)
            : null;

        $context = [
            'offer' => $offer,
            'sender' => $senderName,
            'tone' => $template?->tone,
            'language' => $template?->language,
        ];

        // Satu putaran AI paralel bisa ~1-2 menit untuk model yang lambat.
        set_time_limit(max(300, (int) config('services.ai.timeout', 120) * 2));

        $leads = Lead::whereIn('id', $request->lead_ids)->get()->keyBy('id');

        // Mode AI & hybrid: semua lead dikirim ke AI sekaligus (paralel), bukan satu per satu.
        $aiResults = [];
        if ($mode === 'ai') {
            $aiResults = $ai->generateMany($leads->map(fn ($lead) => $ai->outreachPrompt($lead, $type, $context))->all(), ['temperature' => 0.8]);
        } elseif ($mode === 'hybrid' && $template) {
            $aiResults = $ai->generateMany($leads->map(fn ($lead) => $ai->polishDraftPrompt($lead, MessageTemplate::render($template->body, $lead, $extras), $context))->all());
        }

        $previews = [];

        foreach ($leads as $lead) {
            $subject = null;
            $message = '';
            $isFallback = false;
            $fallbackReason = null;
            $aiResult = $aiResults[$lead->id] ?? null;

            if ($mode === 'template' && $template) {
                // Pure template: instan, tanpa biaya AI
                $message = MessageTemplate::render($template->body, $lead, $extras);
                if ($type === 'email' && $template->subject) {
                    $subject = MessageTemplate::render($template->subject, $lead, $extras);
                }
            } elseif ($mode === 'ai') {
                if (is_string($aiResult)) {
                    ['subject' => $subject, 'message' => $message] = $ai->parseOutreach($aiResult, $lead, $type, $context);
                } else {
                    $isFallback = true;
                    $fallbackReason = $aiResult?->getMessage();
                    [$subject, $message] = $this->fallbackMessage($lead, $type, $offer, $senderName);
                }
            } elseif ($mode === 'hybrid' && $template) {
                $subject = $type === 'email'
                    ? ($template->subject ? MessageTemplate::render($template->subject, $lead, $extras) : $ai->defaultSubject($lead))
                    : null;

                if (is_string($aiResult)) {
                    $message = $ai->cleanMessage($aiResult);
                } else {
                    // Fallback: pakai template yang sudah dirender apa adanya
                    $isFallback = true;
                    $fallbackReason = $aiResult?->getMessage();
                    $message = MessageTemplate::render($template->body, $lead, $extras);
                }
            }

            $previews[] = [
                'lead_id' => $lead->id,
                'business_name' => $lead->business_name,
                'niche' => $lead->niche,
                'city' => $lead->city,
                'email' => $lead->email ?: '',
                'phone' => $lead->phone ?: '',
                'subject' => $subject,
                'message' => trim($message),
                'is_fallback' => $isFallback,
                'fallback_reason' => $fallbackReason,
            ];
        }

        return response()->json([
            'status' => 'success',
            'previews' => $previews,
        ]);
    }

    public function composeSave(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'type' => 'required|in:email,whatsapp',
            'mode' => 'required|in:template,ai,hybrid',
            'template_id' => 'nullable|exists:message_templates,id',
            'messages' => 'required|array',
            'messages.*.lead_id' => 'required|exists:leads,id',
            'messages.*.subject' => 'nullable|string|max:255',
            'messages.*.message' => 'required|string',
        ]);

        $savedCount = 0;

        foreach ($request->messages as $msg) {
            // Perbarui draft pending yang sudah ada untuk lead + campaign + channel yang sama
            OutreachMessage::updateOrCreate(
                [
                    'lead_id' => $msg['lead_id'],
                    'campaign_id' => $request->campaign_id,
                    'type' => $request->type,
                    'status' => 'pending',
                ],
                [
                    'subject' => $msg['subject'] ?? null,
                    'message' => $msg['message'],
                    'mode' => $request->mode,
                    'template_id' => $request->template_id,
                ]
            );
            $savedCount++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "{$savedCount} pesan outreach berhasil disimpan ke pipeline!",
        ]);
    }

    public function composePolish(Request $request, AiService $ai)
    {
        $request->validate([
            'message' => 'required|string',
            'subject' => 'nullable|string',
            'custom_prompt' => 'required|string|max:1000',
            'lead_id' => 'required|exists:leads,id',
            'type' => 'required|in:email,whatsapp',
        ]);

        try {
            $polished = $ai->polishWithInstruction(
                Lead::findOrFail($request->lead_id),
                $request->type,
                $request->message,
                $request->custom_prompt
            );

            return response()->json([
                'status' => 'success',
                'message' => $polished,
            ]);
        } catch (AiException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memoles pesan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Teks cadangan saat AI tidak tersedia, supaya preview tetap bisa dipakai.
     */
    protected function fallbackMessage(Lead $lead, string $type, string $offer, string $senderName): array
    {
        if ($type === 'whatsapp') {
            return [null, "Halo Pimpinan {$lead->business_name} di {$lead->city},\n\nPerkenalkan saya {$senderName}. Kami ingin menawarkan {$offer} untuk meningkatkan branding dan jangkauan digital bisnis {$lead->niche} Anda.\n\nJika tertarik, bolehkah kami kirimkan informasi singkatnya?\n\nSalam,\n{$senderName}"];
        }

        return [
            "Peluang Digitalisasi untuk {$lead->business_name}",
            "Yth. Pimpinan {$lead->business_name},\n\nPerkenalkan saya {$senderName}. Kami tertarik dengan potensi bisnis {$lead->niche} Anda di kota {$lead->city}.\n\nKami memiliki solusi {$offer} yang dapat memperluas pasar Anda secara online. Apakah ada waktu luang 10 menit untuk berdiskusi minggu ini?\n\nSalam hangat,\n{$senderName}",
        ];
    }

    protected function respond(Request $request, bool $success, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => $success ? 'success' : 'error', 'message' => $message], $success ? 200 : $status);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }
}
