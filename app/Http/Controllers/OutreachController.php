<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Campaign;
use App\Models\OutreachMessage;
use App\Services\GroqApiService;
use Illuminate\Http\Request;

class OutreachController extends Controller
{
    public function index()
    {
        $messages = OutreachMessage::with(['lead', 'campaign'])->latest()->paginate(20);
        $campaigns = Campaign::all();
        $leads = Lead::all();
        return view('outreach.index', compact('messages', 'campaigns', 'leads'));
    }

    public function generate(Request $request, GroqApiService $groq)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'type' => 'nullable|in:email,whatsapp',
        ]);

        $campaign = Campaign::findOrFail($request->campaign_id);
        $leads = Lead::whereIn('id', $request->lead_ids)->get();
        $type = $request->input('type', 'email');

        foreach ($leads as $lead) {
            // Avoid duplicate pending outreach of the same type in the same campaign
            $exists = OutreachMessage::where('lead_id', $lead->id)
                ->where('campaign_id', $campaign->id)
                ->where('type', $type)
                ->where('status', 'pending')
                ->exists();

            if ($exists) {
                continue;
            }

            if ($type === 'whatsapp') {
                $prompt = "Tulis pesan WhatsApp outreach singkat (maksimal 2 paragraf), sangat profesional, formal, sopan, dan *to the point* dalam bahasa Indonesia untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN JASA PEMBUATAN WEBSITE PROFESIONAL.\nStrategi penulisan:\n1. Buka dengan sapaan formal (Yth. Bapak/Ibu Pimpinan {$lead->business_name})\n2. Perkenalkan diri sebagai Thoriq dari tim Lefateach (lefateach.com)\n3. Sampaikan secara objektif bahwa memiliki website profesional sangat esensial untuk meningkatkan kredibilitas dan jangkauan pasar di era digital saat ini\n4. Tawarkan diskusi atau konsultasi singkat mengenai potensi digitalisasi bisnis mereka\n5. Gunakan bahasa baku (EYD), jangan gunakan emoji berlebihan, hindari kesan terlalu santai.\nJANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap], pastikan Anda menutup pesan langsung dengan nama pengirim: Thoriq dari Lefateach.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi pesan secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan.";
                $subject = null;
            } else {
                $prompt = "Tulis email outreach profesional dan formal dalam BAHASA INDONESIA untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN JASA PEMBUATAN WEBSITE PROFESIONAL.\nStrategi penulisan:\n1. Mulai dengan sapaan formal (Yth. Bapak/Ibu Pimpinan {$lead->business_name})\n2. Perkenalkan diri sebagai Thoriq dari Lefateach (lefateach.com)\n3. Jelaskan *value proposition* dari memiliki website profesional (kredibilitas, jangkauan klien, efisiensi operasional)\n4. Tawarkan jadwal meeting atau konsultasi gratis selama 15 menit untuk membahas strategi digitalisasi mereka\n5. Tutup dengan salam penutup formal.\nGaya: B2B Profesional, baku, berwibawa, dan tidak terkesan spam. Pendek (maksimal 3 paragraf). JANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap]. WAJIB menggunakan pengirim: Thoriq dari Lefateach.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi email secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan.";
                $subject = "Peluang Peningkatan Kehadiran Digital untuk {$lead->business_name}";
            }

            try {
                $generatedMessage = $groq->generateText($prompt);

                OutreachMessage::create([
                    'lead_id' => $lead->id,
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                    'subject' => $subject,
                    'message' => $generatedMessage,
                    'status' => 'pending',
                ]);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', "Failed generating AI message for {$lead->business_name}: " . $e->getMessage());
            }
        }

        return redirect()->route('outreach.index')->with('success', ucfirst($type) . ' outreach messages generated successfully.');
    }

    public function regenerate(Request $request, OutreachMessage $outreachMessage, GroqApiService $groq)
    {
        $request->validate([
            'custom_prompt' => 'nullable|string|max:1000',
        ]);

        $lead = $outreachMessage->lead;
        $type = $outreachMessage->type;
        $customPrompt = $request->input('custom_prompt');

        if ($type === 'whatsapp') {
            $defaultPrompt = "Tulis pesan WhatsApp outreach singkat (maksimal 2 paragraf), sangat profesional, formal, sopan, dan *to the point* dalam bahasa Indonesia untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN JASA PEMBUATAN WEBSITE PROFESIONAL.\nStrategi penulisan:\n1. Buka dengan sapaan formal (Yth. Bapak/Ibu Pimpinan {$lead->business_name})\n2. Perkenalkan diri sebagai Thoriq dari tim Lefateach (lefateach.com)\n3. Sampaikan secara objektif bahwa memiliki website profesional sangat esensial untuk meningkatkan kredibilitas dan jangkauan pasar di era digital saat ini\n4. Tawarkan diskusi atau konsultasi singkat mengenai potensi digitalisasi bisnis mereka\n5. Gunakan bahasa baku (EYD), jangan gunakan emoji berlebihan, hindari kesan terlalu santai.\nJANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap], pastikan Anda menutup pesan langsung dengan nama pengirim: Thoriq dari Lefateach.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi pesan secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan.";
            $subject = null;
        } else {
            $defaultPrompt = "Tulis email outreach profesional dan formal dalam BAHASA INDONESIA untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN JASA PEMBUATAN WEBSITE PROFESIONAL.\nStrategi penulisan:\n1. Mulai dengan sapaan formal (Yth. Bapak/Ibu Pimpinan {$lead->business_name})\n2. Perkenalkan diri sebagai Thoriq dari Lefateach (lefateach.com)\n3. Jelaskan *value proposition* dari memiliki website profesional (kredibilitas, jangkauan klien, efisiensi operasional)\n4. Tawarkan jadwal meeting atau konsultasi gratis selama 15 menit untuk membahas strategi digitalisasi mereka\n5. Tutup dengan salam penutup formal.\nGaya: B2B Profesional, baku, berwibawa, dan tidak terkesan spam. Pendek (maksimal 3 paragraf). JANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap]. WAJIB menggunakan pengirim: Thoriq dari Lefateach.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi email secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan.";
            $subject = "Peluang Peningkatan Kehadiran Digital untuk {$lead->business_name}";
        }

        $prompt = $customPrompt ? $customPrompt . "\n\nKonteks Bisnis:\nNama Bisnis: {$lead->business_name}\nBidang: {$lead->niche}\nKota: {$lead->city}\nTipe Pesan: {$type}\nWAJIB menggunakan nama pengirim Thoriq dari Lefateach (lefateach.com) di akhir pesan.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi pesan secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah pesan...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan." : $defaultPrompt;

        try {
            $generatedMessage = $groq->generateText($prompt);

            return response()->json([
                'status' => 'success',
                'subject' => $subject,
                'message' => $generatedMessage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to regenerate AI message: ' . $e->getMessage()
            ], 500);
        }
    }

    public function send(OutreachMessage $outreachMessage)
    {
        $lead = $outreachMessage->lead;

        if ($outreachMessage->type === 'whatsapp') {
            if (empty($lead->phone)) {
                return redirect()->back()->with('error', "Cannot send WhatsApp. {$lead->business_name} does not have a phone number listed.");
            }

            // Clean phone number: remove non-digits
            $phone = preg_replace('/[^0-9]/', '', $lead->phone);
            
            // Format for Indonesian WhatsApp numbers
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }

            $outreachMessage->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $waUrl = "https://wa.me/{$phone}?text=" . rawurlencode($outreachMessage->message);
            return redirect()->away($waUrl);
        }

        // Email flow
        if (empty($lead->email)) {
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => "Cannot send email. {$lead->business_name} does not have an email address listed."], 422);
            }
            return redirect()->back()->with('error', "Cannot send email. {$lead->business_name} does not have an email address listed.");
        }

        try {
            \Illuminate\Support\Facades\Mail::to($lead->email)->send(
                new \App\Mail\OutreachMail($outreachMessage->subject ?? 'Outreach Mail', $outreachMessage->message)
            );

            $outreachMessage->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            if (request()->expectsJson()) {
                return response()->json(['status' => 'success', 'message' => 'Email outreach sent successfully!']);
            }

            return redirect()->back()->with('success', 'Email outreach sent successfully!');
        } catch (\Exception $e) {
            $outreachMessage->update([
                'status' => 'failed',
            ]);

            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, OutreachMessage $outreachMessage)
    {
        $request->validate([
            'status' => 'required|in:pending,sent,failed,replied',
        ]);

        $outreachMessage->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Outreach status updated successfully.');
    }

    public function update(Request $request, OutreachMessage $outreachMessage)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $outreachMessage->update([
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return redirect()->back()->with('success', 'Outreach message updated successfully.');
    }

    public function destroy(OutreachMessage $outreachMessage)
    {
        $outreachMessage->delete();
        return redirect()->back()->with('success', 'Outreach message deleted successfully.');
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:outreach_messages,id',
            'action' => 'required|in:delete,status_pending,status_sent,status_replied,status_failed',
        ]);

        if ($request->action === 'delete') {
            OutreachMessage::whereIn('id', $request->ids)->delete();
            $message = 'Selected outreach messages deleted.';
        } else {
            $status = str_replace('status_', '', $request->action);
            OutreachMessage::whereIn('id', $request->ids)->update(['status' => $status]);
            $message = 'Selected outreach messages marked as ' . ucfirst($status) . '.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function getTemplates(Request $request)
    {
        $request->validate([
            'channel' => 'nullable|in:email,whatsapp',
            'niche' => 'nullable|string',
        ]);

        $query = \App\Models\MessageTemplate::active();

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('niche')) {
            $query->where('niche', 'like', '%' . $request->niche . '%');
        }

        $templates = $query->get(['id', 'name', 'niche', 'channel', 'subject', 'body', 'tone']);

        return response()->json([
            'status' => 'success',
            'templates' => $templates
        ]);
    }

    public function composePreview(Request $request, GroqApiService $groq)
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

        $leadIds = $request->lead_ids;
        $mode = $request->mode;
        $templateId = $request->template_id;
        $type = $request->type; // email or whatsapp
        $offer = $request->input('offer', 'Jasa Pembuatan Website');
        $senderName = $request->input('sender_name', 'Thoriq dari Lefateach');

        $leads = Lead::whereIn('id', $leadIds)->get();
        $previews = [];

        // Load template if needed
        $template = null;
        if (in_array($mode, ['template', 'hybrid']) && $templateId) {
            $template = \App\Models\MessageTemplate::findOrFail($templateId);
        }

        foreach ($leads as $lead) {
            $subject = null;
            $message = '';
            $isFallback = false;
            $fallbackReason = null;

            if ($mode === 'template') {
                // Pure Master Template Mode (instant, <100ms, no AI cost!)
                if ($template) {
                    $bodyTpl = $template->body;
                    $subjectTpl = $template->subject;
                    
                    $message = \App\Models\MessageTemplate::render($bodyTpl, $lead, [
                        'offer' => $offer,
                        'sender_name' => $senderName
                    ]);
                    
                    if ($type === 'email' && $subjectTpl) {
                        $subject = \App\Models\MessageTemplate::render($subjectTpl, $lead, [
                            'offer' => $offer,
                            'sender_name' => $senderName
                        ]);
                    }
                }
            } elseif ($mode === 'ai') {
                // AI Generate Mode
                $lang = ($template && $template->language === 'en') ? 'English' : 'Bahasa Indonesia';
                $tone = ($template) ? $template->tone : 'formal';

                if ($type === 'whatsapp') {
                    $prompt = "Tulis pesan WhatsApp outreach singkat (maksimal 2 paragraf), sangat profesional, bernada {$tone}, sopan, dan *to the point* dalam {$lang} untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN {$offer}.\nStrategi penulisan:\n1. Sapaan pembuka hangat dan sopan\n2. Perkenalkan diri sebagai {$senderName}\n3. Sampaikan nilai tambah secara objektif dan tawarkan diskusi singkat\n4. Gunakan bahasa yang natural, jangan gunakan emoji berlebihan.\nJANGAN gunakan placeholder seperti [Nama Anda], tutup langsung dengan pengirim: {$senderName}.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi pesan secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan.";
                } else {
                    $prompt = "Tulis email outreach profesional dan persuasif dalam {$lang} untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN {$offer}.\nNada bicara: {$tone}.\nStrategi penulisan:\n1. Mulai dengan sapaan sopan\n2. Perkenalkan diri sebagai {$senderName}\n3. Jelaskan *value proposition* dari {$offer} secara elegan untuk industri {$lead->niche}\n4. Tawarkan jadwal meeting atau konsultasi gratis selama 15 menit sebagai CTA\n5. Tutup secara formal.\nPendek (maksimal 3 paragraf). JANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap]. WAJIB menggunakan pengirim: {$senderName}.\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi email secara langsung. JANGAN ADA teks pengantar seperti \"Berikut adalah...\".\n2. JANGAN gunakan formatting markdown (seperti ** atau *).\n3. Langsung mulai dari sapaan.";
                    $subject = "Kolaborasi Strategis untuk {$lead->business_name}";
                }

                try {
                    $message = $groq->generateText($prompt);
                } catch (\Exception $e) {
                    $isFallback = true;
                    $fallbackReason = $e->getMessage();
                    
                    // Fallback baseline text
                    if ($type === 'whatsapp') {
                        $message = "Halo Pimpinan {$lead->business_name} di {$lead->city},\n\nPerkenalkan saya {$senderName}. Kami ingin menawarkan {$offer} untuk meningkatkan branding dan jangkauan digital bisnis {$lead->niche} Anda.\n\nJika tertarik, bolehkah kami kirimkan brosur singkatnya?\n\nSalam,\n{$senderName}";
                    } else {
                        $subject = "Peluang Digitalisasi untuk {$lead->business_name}";
                        $message = "Yth. Pimpinan {$lead->business_name},\n\nPerkenalkan saya {$senderName}. Kami sangat tertarik dengan potensi bisnis {$lead->niche} Anda di kota {$lead->city}.\n\nKami memiliki solusi {$offer} yang dapat memperluas pasar Anda secara online. Apakah ada waktu luang 10 menit untuk berdiskusi minggu ini?\n\nSalam hangat,\n{$senderName}";
                    }
                }
            } elseif ($mode === 'hybrid') {
                // Hybrid: Template + AI Polish Mode
                if ($template) {
                    $bodyTpl = $template->body;
                    $subjectTpl = $template->subject;
                    
                    $renderedBody = \App\Models\MessageTemplate::render($bodyTpl, $lead, [
                        'offer' => $offer,
                        'sender_name' => $senderName
                    ]);
                    
                    if ($type === 'email' && $subjectTpl) {
                        $subject = \App\Models\MessageTemplate::render($subjectTpl, $lead, [
                            'offer' => $offer,
                            'sender_name' => $senderName
                        ]);
                    } else {
                        $subject = "Peluang Kolaborasi untuk {$lead->business_name}";
                    }

                    $lang = ($template->language === 'en') ? 'English' : 'Bahasa Indonesia';
                    $tone = $template->tone;

                    $prompt = "Anda adalah sales copywriter handal. Kami memiliki draf pesan outreach B2B yang sudah diisi dengan data bisnis target.\nTugas Anda adalah memoles dan mempersonalisasi draf pesan ini agar terasa lebih natural, menarik, dan sangat relevan dengan target bisnis, tetapi tetap mempertahankan:\n1. Nada bicara (tone): {$tone}\n2. Call to Action (CTA) asli\n3. Informasi pengirim asli: {$senderName}\n4. Bahasa penulisan: {$lang}\n\nDraf Asli:\n{$renderedBody}\n\nDetail Bisnis Target:\nNama Bisnis: {$lead->business_name}\nBidang: {$lead->niche}\nKota: {$lead->city}\n\nATURAN SUPER KETAT:\n1. HANYA kembalikan isi pesan yang sudah dipoles secara langsung. JANGAN ADA kata pengantar, penjelasan, atau penutup tambahan.\n2. JANGAN gunakan formatting markdown seperti tebal (**) atau miring (*).\n3. Langsung mulai dari sapaan pembuka.";

                    try {
                        $message = $groq->generateText($prompt);
                    } catch (\Exception $e) {
                        $isFallback = true;
                        $fallbackReason = $e->getMessage();
                        
                        // Fallback: use raw rendered template directly!
                        $message = $renderedBody;
                    }
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
            'previews' => $previews
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

        $campaignId = $request->campaign_id;
        $type = $request->type;
        $mode = $request->mode;
        $templateId = $request->template_id;
        $messages = $request->messages;

        $savedCount = 0;

        foreach ($messages as $msg) {
            // Check if active pending outreach of same type exists
            $existing = OutreachMessage::where('lead_id', $msg['lead_id'])
                ->where('campaign_id', $campaignId)
                ->where('type', $type)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                $existing->update([
                    'subject' => $msg['subject'] ?? null,
                    'message' => $msg['message'],
                    'mode' => $mode,
                    'template_id' => $templateId,
                ]);
            } else {
                OutreachMessage::create([
                    'lead_id' => $msg['lead_id'],
                    'campaign_id' => $campaignId,
                    'type' => $type,
                    'subject' => $msg['subject'] ?? null,
                    'message' => $msg['message'],
                    'status' => 'pending',
                    'mode' => $mode,
                    'template_id' => $templateId,
                ]);
            }
            $savedCount++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully saved {$savedCount} outreach messages to pipeline!"
        ]);
    }

    public function composePolish(Request $request, GroqApiService $groq)
    {
        $request->validate([
            'message' => 'required|string',
            'subject' => 'nullable|string',
            'custom_prompt' => 'required|string|max:1000',
            'lead_id' => 'required|exists:leads,id',
            'type' => 'required|in:email,whatsapp',
        ]);

        $lead = Lead::findOrFail($request->lead_id);
        $currentMessage = $request->message;
        $customPrompt = $request->custom_prompt;
        $type = $request->type;

        $prompt = "Anda adalah sales copywriter handal. Kami sedang memproses pesan outreach {$type} untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\n\nBerikut adalah draf pesan saat ini:\n\"\"\"\n{$currentMessage}\n\"\"\"\n\nTolong poles pesan di atas berdasarkan instruksi khusus berikut:\n\"{$customPrompt}\"\n\nATURAN SUPER KETAT:\n1. HANYA berikan isi pesan yang sudah dipoles secara langsung. JANGAN ADA kata pengantar, penjelasan, atau penutup tambahan.\n2. JANGAN gunakan formatting markdown seperti tebal (**) atau miring (*).\n3. Jaga agar nama pengirim asli dan CTA penting tetap utuh.";

        try {
            $polished = $groq->generateText($prompt);
            return response()->json([
                'status' => 'success',
                'message' => $polished
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to polish message: ' . $e->getMessage()
            ], 500);
        }
    }
}
