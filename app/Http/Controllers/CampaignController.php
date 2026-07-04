<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Services\GroqApiService;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::latest()->get();
        return view('campaigns.index', compact('campaigns'));
    }
    public function filterLeads(Request $request)
    {
        $query = Lead::latest();

        // 1. Search Query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('website', 'like', "%{$search}%")
                  ->orWhere('niche', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // 2. Niche Filter (supports single value and multiple values)
        $nicheFilter = $request->input('niche', []);
        if (!is_array($nicheFilter)) {
            $nicheFilter = [$nicheFilter];
        }
        $nicheFilter = array_values(array_filter($nicheFilter, fn($v) => $v !== null && $v !== ''));
        if (!empty($nicheFilter)) {
            $query->whereIn('niche', $nicheFilter);
        }

        // 3. City Filter (supports single value and multiple values)
        $cityFilter = $request->input('city', []);
        if (!is_array($cityFilter)) {
            $cityFilter = [$cityFilter];
        }
        $cityFilter = array_values(array_filter($cityFilter, fn($v) => $v !== null && $v !== ''));
        if (!empty($cityFilter)) {
            $query->whereIn('city', $cityFilter);
        }

        // 4. Contact Status Filters
        // Handphone (phone)
        if ($request->filled('has_phone')) {
            if ($request->has_phone === 'yes') {
                $query->whereNotNull('phone')->where('phone', '!=', '');
            } elseif ($request->has_phone === 'no') {
                $query->where(function($q) {
                    $q->whereNull('phone')->orWhere('phone', '');
                });
            }
        }

        // Email
        if ($request->filled('has_email')) {
            if ($request->has_email === 'yes') {
                $query->whereNotNull('email')->where('email', '!=', '');
            } elseif ($request->has_email === 'no') {
                $query->where(function($q) {
                    $q->whereNull('email')->orWhere('email', '');
                });
            }
        }

        // Website
        if ($request->filled('has_website')) {
            if ($request->has_website === 'yes') {
                $query->whereNotNull('website')->where('website', '!=', '');
            } elseif ($request->has_website === 'no') {
                $query->where(function($q) {
                    $q->whereNull('website')->orWhere('website', '');
                });
            }
        }

        // Paginate results
        $leads = $query->paginate(15);

        // Return the response as JSON
        return response()->json([
            'status' => 'success',
            'leads' => $leads->items(),
            'pagination' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'total' => $leads->total(),
                'per_page' => $leads->perPage(),
                'has_more' => $leads->hasMorePages(),
            ]
        ]);
    }

    public function create()
    {
        $templates = \App\Models\MessageTemplate::active()->get();
        $niches = Lead::select('niche')->distinct()->whereNotNull('niche')->where('niche', '!=', '')->pluck('niche')->toArray();
        $cities = Lead::select('city')->distinct()->whereNotNull('city')->where('city', '!=', '')->pluck('city')->toArray();
        return view('campaigns.create', compact('templates', 'niches', 'cities'));
    }

    public function store(Request $request, GroqApiService $groq)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'niche' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'exists:leads,id',
            'auto_generate' => 'nullable|boolean',
            'outreach_mode' => 'nullable|in:ai_generate,template',
            'template_id' => 'nullable|exists:message_templates,id',
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,whatsapp',
        ]);

        $campaign = Campaign::create([
            'name' => $data['name'],
            'niche' => $data['niche'],
            'location' => $data['location'],
        ]);

        $leadIds = $data['lead_ids'] ?? [];
        $autoGenerate = $request->has('auto_generate');
        $outreachMode = $data['outreach_mode'] ?? 'ai_generate';
        $templateId = $data['template_id'] ?? null;
        $channels = $data['channels'] ?? ['email', 'whatsapp'];

        if (!empty($leadIds) && $autoGenerate) {
            $leads = Lead::whereIn('id', $leadIds)->get();

            // Load template if in template mode
            $template = null;
            if ($outreachMode === 'template' && $templateId) {
                $template = \App\Models\MessageTemplate::find($templateId);
            }

            foreach ($leads as $lead) {
                foreach ($channels as $channel) {
                    if ($outreachMode === 'template' && $template) {
                        // Render template instantly without AI cost!
                        $body = \App\Models\MessageTemplate::render($template->body, $lead, [
                            'offer' => 'Jasa Pembuatan Website Profesional',
                            'sender_name' => 'Thoriq dari Lefateach'
                        ]);
                        $subject = $channel === 'email' && $template->subject 
                            ? \App\Models\MessageTemplate::render($template->subject, $lead, [
                                'offer' => 'Jasa Pembuatan Website Profesional',
                                'sender_name' => 'Thoriq dari Lefateach'
                            ]) 
                            : null;

                        OutreachMessage::create([
                            'lead_id' => $lead->id,
                            'campaign_id' => $campaign->id,
                            'type' => $channel,
                            'subject' => $subject,
                            'message' => $body,
                            'status' => 'pending',
                            'mode' => 'template',
                            'template_id' => $template->id,
                        ]);
                    } else {
                        // AI Generate mode
                        if ($channel === 'whatsapp') {
                            $prompt = "Tulis pesan WhatsApp outreach singkat (maksimal 2 paragraf), sangat profesional, formal, sopan, dan *to the point* dalam bahasa Indonesia untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN JASA PEMBUATAN WEBSITE PROFESIONAL.\nStrategi penulisan:\n1. Buka dengan sapaan formal (Yth. Bapak/Ibu Pimpinan {$lead->business_name})\n2. Perkenalkan diri sebagai konsultan digital\n3. Sampaikan secara objektif bahwa memiliki website profesional sangat esensial untuk meningkatkan kredibilitas dan jangkauan pasar di era digital saat ini\n4. Tawarkan diskusi atau konsultasi singkat mengenai potensi digitalisasi bisnis mereka\n5. Gunakan bahasa baku (EYD), jangan gunakan emoji berlebihan, hindari kesan terlalu santai.\nJANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap], langsung tutup dengan salam hormat.";
                            $subject = null;
                        } else {
                            $prompt = "Tulis email outreach profesional dan formal dalam BAHASA INDONESIA untuk pemilik bisnis {$lead->business_name} di bidang {$lead->niche} di kota {$lead->city}.\nTujuan: MENAWARKAN JASA PEMBUATAN WEBSITE PROFESIONAL.\nStrategi penulisan:\n1. Mulai dengan sapaan formal (Yth. Bapak/Ibu Pimpinan {$lead->business_name})\n2. Perkenalkan tujuan email secara langsung dan profesional\n3. Jelaskan *value proposition* dari memiliki website profesional (kredibilitas, jangkauan klien, efisiensi operasional)\n4. Tawarkan jadwal meeting atau konsultasi gratis selama 15 menit untuk membahas strategi digitalisasi mereka\n5. Tutup dengan salam penutup formal.\nGaya: B2B Profesional, baku, berwibawa, dan tidak terkesan spam. Pendek (maksimal 3 paragraf). JANGAN gunakan placeholder seperti [Nama Anda] atau [Nama Lengkap].";
                            $subject = "Peluang Peningkatan Kehadiran Digital untuk {$lead->business_name}";
                        }

                        try {
                            $generatedMessage = $groq->generateText($prompt);

                            OutreachMessage::create([
                                'lead_id' => $lead->id,
                                'campaign_id' => $campaign->id,
                                'type' => $channel,
                                'subject' => $subject,
                                'message' => $generatedMessage,
                                'status' => 'pending',
                                'mode' => 'ai',
                            ]);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Failed to generate AI message for lead {$lead->id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $message = 'Campaign created successfully.';
        if ($autoGenerate && !empty($leadIds)) {
            $message .= ' Outreach messages have been generated for ' . count($leadIds) . ' selected leads!';
        }

        return redirect()->route('campaigns.index')->with('success', $message);
    }

    public function suggestLeads(Request $request, GroqApiService $groq)
    {
        $niche = trim($request->input('niche'));
        $location = trim($request->input('location'));

        if (empty($niche) && empty($location)) {
            return response()->json(['ids' => []]);
        }

        // 1. Get all leads to match
        $allLeads = Lead::all(['id', 'business_name', 'niche', 'city']);

        if ($allLeads->isEmpty()) {
            return response()->json(['ids' => []]);
        }

        // 2. Perform a fast keyword pre-filter in PHP to keep prompt tiny and highly accurate
        $filteredLeads = $allLeads->filter(function ($lead) use ($niche, $location) {
            $nicheMatch = true;
            $locationMatch = true;

            if (!empty($niche)) {
                $nicheMatch = stripos($lead->niche, $niche) !== false 
                           || stripos($niche, $lead->niche) !== false
                           || stripos($lead->business_name, $niche) !== false;
            }

            if (!empty($location)) {
                $locationMatch = stripos($lead->city, $location) !== false 
                              || stripos($location, $lead->city) !== false;
            }

            return $nicheMatch && $locationMatch;
        });

        // If no keyword match at all, let's try a broader filter (matching either niche OR location)
        if ($filteredLeads->isEmpty()) {
            $filteredLeads = $allLeads->filter(function ($lead) use ($niche, $location) {
                $nicheMatch = false;
                $locationMatch = false;

                if (!empty($niche)) {
                    $nicheMatch = stripos($lead->niche, $niche) !== false 
                               || stripos($niche, $lead->niche) !== false
                               || stripos($lead->business_name, $niche) !== false;
                }

                if (!empty($location)) {
                    $locationMatch = stripos($lead->city, $location) !== false 
                                  || stripos($location, $lead->city) !== false;
                }

                return $nicheMatch || $locationMatch;
            });
        }

        // Map filtered leads for AI analysis
        $leadsData = $filteredLeads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'name' => $lead->business_name,
                'niche' => $lead->niche,
                'city' => $lead->city,
            ];
        })->values()->toArray();

        // If we still have no candidates, return empty list immediately
        if (empty($leadsData)) {
            return response()->json(['ids' => []]);
        }

        // 3. AI Smart Matching Prompt (with limited candidates, highly accurate!)
        $prompt = "You are a smart AI matching assistant. A campaign is being created for the niche \"$niche\" in the location \"$location\".
Analyze this list of lead candidates and select the ones that are highly relevant to this niche and location.
Respond with a strict raw JSON object containing only a key \"ids\" mapping to an array of integers representing the matching lead IDs. Do not write any markdown formatting, conversational text, or code block wrapper.
Example response:
{\"ids\":[1,3,5]}

Candidates List:
" . json_encode($leadsData);

        try {
            $response = $groq->generateText($prompt);
            $response = trim($response);

            // Bulletproof regex to extract JSON { ... } from the response text
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonString = $matches[0];
            } else {
                $jsonString = $response;
            }

            $data = json_decode($jsonString, true);
            
            // If json_decode failed or returned empty/invalid structure, let's force fallback
            if ($data === null || !isset($data['ids']) || !is_array($data['ids'])) {
                throw new \Exception("Invalid JSON structure received from AI.");
            }

            $ids = array_map('intval', $data['ids']);
            return response()->json(['ids' => $ids]);

        } catch (\Exception $e) {
            // Fallback to all filtered candidate IDs if AI analysis failed or format was invalid!
            $fallbackIds = $filteredLeads->pluck('id')->toArray();
            return response()->json(['ids' => $fallbackIds]);
        }
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['outreachMessages.lead']);
        
        $messages = $campaign->outreachMessages;
        $totalMessages = $messages->count();
        $sentCount = $messages->where('status', 'sent')->count();
        $pendingCount = $messages->where('status', 'pending')->count();
        $failedCount = $messages->where('status', 'failed')->count();
        $repliedCount = $messages->where('status', 'replied')->count();
        
        // Group messages by lead for easy listing in the details page
        $groupedMessages = $messages->groupBy('lead_id');

        return view('campaigns.show', compact(
            'campaign',
            'messages',
            'totalMessages',
            'sentCount',
            'pendingCount',
            'failedCount',
            'repliedCount',
            'groupedMessages'
        ));
    }

    public function edit(Campaign $campaign)
    {
        $templates = \App\Models\MessageTemplate::active()->get();
        $selectedLeadIds = $campaign->outreachMessages->pluck('lead_id')->unique()->toArray();
        $niches = Lead::select('niche')->distinct()->whereNotNull('niche')->where('niche', '!=', '')->pluck('niche')->toArray();
        $cities = Lead::select('city')->distinct()->whereNotNull('city')->where('city', '!=', '')->pluck('city')->toArray();
        
        return view('campaigns.edit', compact('campaign', 'templates', 'selectedLeadIds', 'niches', 'cities'));
    }

    public function update(Request $request, Campaign $campaign, GroqApiService $groq)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'niche' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'exists:leads,id',
            'auto_generate' => 'nullable|boolean',
            'outreach_mode' => 'nullable|in:ai_generate,template',
            'template_id' => 'nullable|exists:message_templates,id',
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,whatsapp',
        ]);

        $campaign->update([
            'name' => $data['name'],
            'niche' => $data['niche'],
            'location' => $data['location'],
        ]);

        $newLeadIds = $data['lead_ids'] ?? [];
        $currentLeadIds = $campaign->outreachMessages->pluck('lead_id')->unique()->toArray();

        // Leads to remove: currently in campaign but not in new selection
        $leadsToRemove = array_diff($currentLeadIds, $newLeadIds);
        if (!empty($leadsToRemove)) {
            $campaign->outreachMessages()->whereIn('lead_id', $leadsToRemove)->delete();
        }

        // Leads to add: in new selection but not in campaign
        $leadsToAdd = array_diff($newLeadIds, $currentLeadIds);
        $autoGenerate = $request->has('auto_generate');
        $outreachMode = $data['outreach_mode'] ?? 'ai_generate';
        $templateId = $data['template_id'] ?? null;
        $channels = $data['channels'] ?? ['email', 'whatsapp'];

        if (!empty($leadsToAdd) && $autoGenerate) {
            $addedLeads = Lead::whereIn('id', $leadsToAdd)->get();

            // Load template if in template mode
            $template = null;
            if ($outreachMode === 'template' && $templateId) {
                $template = \App\Models\MessageTemplate::find($templateId);
            }

            foreach ($addedLeads as $lead) {
                foreach ($channels as $channel) {
                    if ($outreachMode === 'template' && $template) {
                        // Render template instantly without AI cost!
                        $body = \App\Models\MessageTemplate::render($template->body, $lead, [
                            'offer' => 'Jasa Pembuatan Website Profesional',
                            'sender_name' => 'Thoriq dari Lefateach'
                        ]);
                        $subject = $channel === 'email' && $template->subject 
                            ? \App\Models\MessageTemplate::render($template->subject, $lead, [
                                'offer' => 'Jasa Pembuatan Website Profesional',
                                'sender_name' => 'Thoriq dari Lefateach'
                            ]) 
                            : null;

                        OutreachMessage::create([
                            'lead_id' => $lead->id,
                            'campaign_id' => $campaign->id,
                            'type' => $channel,
                            'subject' => $subject,
                            'message' => $body,
                            'status' => 'pending',
                            'mode' => 'template',
                            'template_id' => $template->id,
                        ]);
                    } else {
                        // AI Generate mode
                        if ($channel === 'whatsapp') {
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
                                'type' => $channel,
                                'subject' => $subject,
                                'message' => $generatedMessage,
                                'status' => 'pending',
                                'mode' => 'ai',
                            ]);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Failed to generate AI message for lead {$lead->id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        $message = 'Campaign updated successfully.';
        if ($autoGenerate && !empty($leadsToAdd)) {
            $message .= ' AI Outreach messages generated for ' . count($leadsToAdd) . ' newly added leads!';
        }

        return redirect()->route('campaigns.index')->with('success', $message);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}
