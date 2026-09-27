<?php

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\MessageTemplate;
use App\Services\AiService;
use App\Services\OutreachGenerator;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::withCount([
            'outreachMessages',
            'outreachMessages as delivered_count' => fn ($q) => $q->whereIn('status', ['sent', 'replied']),
            'outreachMessages as replied_count' => fn ($q) => $q->where('status', 'replied'),
        ])->latest()->get();

        return view('campaigns.index', compact('campaigns'));
    }

    public function filterLeads(Request $request)
    {
        $query = Lead::latest();

        // 1. Search Query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%")
                    ->orWhere('niche', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // 2. Niche & City Filter (single value or multiple values)
        foreach (['niche' => 'niche', 'city' => 'city'] as $input => $column) {
            $values = array_values(array_filter((array) $request->input($input, []), fn ($v) => $v !== null && $v !== ''));
            if (! empty($values)) {
                $query->whereIn($column, $values);
            }
        }

        // 3. Contact Status Filters
        $query->filter($request->only(['has_phone', 'has_email', 'has_website']));

        $leads = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'leads' => $leads->items(),
            'pagination' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'total' => $leads->total(),
                'per_page' => $leads->perPage(),
                'has_more' => $leads->hasMorePages(),
            ],
        ]);
    }

    public function create()
    {
        $templates = MessageTemplate::active()->get();
        [$niches, $cities] = $this->filterOptions();

        return view('campaigns.create', compact('templates', 'niches', 'cities'));
    }

    public function store(Request $request, OutreachGenerator $generator)
    {
        $data = $this->validateCampaign($request);

        $campaign = Campaign::create([
            'name' => $data['name'],
            'niche' => $data['niche'],
            'location' => $data['location'],
        ]);

        $leadIds = $data['lead_ids'] ?? [];
        $message = 'Campaign berhasil dibuat.';

        if (! empty($leadIds) && $request->boolean('auto_generate')) {
            $message .= ' '.$this->generateMessages($generator, $campaign, $leadIds, $data);
        }

        return redirect()->route('campaigns.show', $campaign)->with('success', $message);
    }

    public function suggestLeads(Request $request, AiService $ai)
    {
        $niche = trim((string) $request->input('niche'));
        $location = trim((string) $request->input('location'));

        if ($niche === '' && $location === '') {
            return response()->json(['ids' => []]);
        }

        $allLeads = Lead::all(['id', 'business_name', 'niche', 'city']);

        // 1. Pre-filter keyword di PHP supaya prompt kecil dan akurat
        $matches = function ($lead, bool $requireBoth) use ($niche, $location) {
            $nicheMatch = $niche !== '' && (
                stripos((string) $lead->niche, $niche) !== false
                || ($lead->niche && stripos($niche, $lead->niche) !== false)
                || stripos($lead->business_name, $niche) !== false
            );
            $locationMatch = $location !== '' && (
                stripos((string) $lead->city, $location) !== false
                || ($lead->city && stripos($location, $lead->city) !== false)
            );

            if ($requireBoth) {
                return ($niche === '' || $nicheMatch) && ($location === '' || $locationMatch);
            }

            return $nicheMatch || $locationMatch;
        };

        $filtered = $allLeads->filter(fn ($lead) => $matches($lead, true));

        // Tidak ada yang cocok keduanya: longgarkan jadi niche ATAU lokasi
        if ($filtered->isEmpty()) {
            $filtered = $allLeads->filter(fn ($lead) => $matches($lead, false));
        }

        if ($filtered->isEmpty()) {
            return response()->json(['ids' => []]);
        }

        $candidates = $filtered->map(fn ($lead) => [
            'id' => $lead->id,
            'name' => $lead->business_name,
            'niche' => $lead->niche,
            'city' => $lead->city,
        ])->values()->all();

        // 2. AI Smart Matching; jika AI gagal/tidak dikonfigurasi, pakai hasil keyword match
        try {
            return response()->json(['ids' => $ai->matchLeadIds($niche, $location, $candidates), 'source' => 'ai']);
        } catch (AiException $e) {
            return response()->json(['ids' => $filtered->pluck('id')->values()->all(), 'source' => 'keyword', 'warning' => $e->getMessage()]);
        }
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['outreachMessages.lead']);

        $messages = $campaign->outreachMessages;
        $totalMessages = $messages->count();
        $sentCount = $messages->whereIn('status', ['sent', 'replied'])->count();
        $pendingCount = $messages->whereIn('status', ['pending', 'queued'])->count();
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

    /**
     * Progres generate pesan AI di background (dipolling oleh halaman detail campaign).
     */
    public function progress(Campaign $campaign)
    {
        return response()->json([
            'total' => $campaign->generation_total,
            'done' => $campaign->generation_done,
            'failed' => $campaign->generation_failed,
            'generating' => $campaign->isGenerating(),
            'messages' => $campaign->outreachMessages()->count(),
        ]);
    }

    public function edit(Campaign $campaign)
    {
        $templates = MessageTemplate::active()->get();
        $selectedLeadIds = $campaign->outreachMessages->pluck('lead_id')->unique()->values()->toArray();
        [$niches, $cities] = $this->filterOptions();

        return view('campaigns.edit', compact('campaign', 'templates', 'selectedLeadIds', 'niches', 'cities'));
    }

    public function update(Request $request, Campaign $campaign, OutreachGenerator $generator)
    {
        $data = $this->validateCampaign($request);

        $campaign->update([
            'name' => $data['name'],
            'niche' => $data['niche'],
            'location' => $data['location'],
        ]);

        $newLeadIds = array_map('intval', $data['lead_ids'] ?? []);
        $currentLeadIds = $campaign->outreachMessages()->pluck('lead_id')->unique()->all();

        // Lead yang di-unselect: hapus hanya draft yang belum terkirim. Riwayat sent/replied tetap disimpan.
        $message = 'Campaign berhasil diperbarui.';
        $leadsToRemove = array_diff($currentLeadIds, $newLeadIds);

        if (! empty($leadsToRemove)) {
            $campaign->outreachMessages()
                ->whereIn('lead_id', $leadsToRemove)
                ->whereIn('status', ['pending', 'queued', 'failed'])
                ->delete();

            $kept = $campaign->outreachMessages()->whereIn('lead_id', $leadsToRemove)->distinct()->count('lead_id');
            if ($kept > 0) {
                $message .= " {$kept} lead tetap tercatat di campaign karena sudah punya pesan terkirim/dibalas.";
            }
        }

        $leadsToAdd = array_values(array_diff($newLeadIds, $currentLeadIds));

        if (! empty($leadsToAdd) && $request->boolean('auto_generate')) {
            $message .= ' '.$this->generateMessages($generator, $campaign, $leadsToAdd, $data);
        }

        return redirect()->route('campaigns.show', $campaign)->with('success', $message);
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign berhasil dihapus.');
    }

    protected function validateCampaign(Request $request): array
    {
        return $request->validate([
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
    }

    protected function generateMessages(OutreachGenerator $generator, Campaign $campaign, array $leadIds, array $data): string
    {
        $mode = $data['outreach_mode'] ?? 'ai_generate';
        $template = $mode === 'template' && ! empty($data['template_id']) ? MessageTemplate::find($data['template_id']) : null;

        $result = $generator->generate(
            $campaign,
            Lead::whereIn('id', $leadIds)->get(),
            $data['channels'] ?? ['email', 'whatsapp'],
            $template ? 'template' : 'ai',
            $template
        );

        $parts = [];
        if ($result['created']) {
            $parts[] = "{$result['created']} pesan dibuat dari template";
        }
        if ($result['queued']) {
            $parts[] = "{$result['queued']} pesan AI sedang digenerate di background";
        }
        if ($result['skipped']) {
            $parts[] = "{$result['skipped']} dilewati karena draft-nya sudah ada";
        }

        return $parts ? ucfirst(implode(', ', $parts)).'.' : '';
    }

    protected function filterOptions(): array
    {
        $niches = Lead::select('niche')->distinct()->whereNotNull('niche')->where('niche', '!=', '')->pluck('niche')->toArray();
        $cities = Lead::select('city')->distinct()->whereNotNull('city')->where('city', '!=', '')->pluck('city')->toArray();

        return [$niches, $cities];
    }
}
