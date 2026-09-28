<?php

namespace App\Http\Controllers;

use App\Exceptions\CrawlException;
use App\Helpers\LeadSelection;
use App\Helpers\Url;
use App\Jobs\EnrichLeadJob;
use App\Jobs\ScrapeGoogleMapsJob;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\ScrapingNotification;
use App\Services\WebsiteCrawlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(LeadSelection::FILTERS);
        $sort = $request->query('sort') === 'score' ? 'score' : 'latest';

        $query = Lead::query()->filter($filters);
        $sort === 'score' ? $query->orderByDesc('score')->orderByDesc('id') : $query->latest();

        $leads = $query->paginate(20)->withQueryString();
        $pendingScrapes = $this->pendingScrapes();
        $campaigns = Campaign::all();
        $stages = Lead::STAGES;

        return view('leads.index', compact('leads', 'pendingScrapes', 'campaigns', 'stages', 'filters', 'sort'));
    }

    public function show(Lead $lead)
    {
        $lead->load(['notes.user', 'outreachMessages' => fn ($q) => $q->with('campaign')->latest()]);
        $stages = Lead::STAGES;

        return view('leads.show', compact('lead', 'stages'));
    }

    public function store(Request $request)
    {
        Lead::create($this->validateLead($request));

        return redirect()->route('leads.index')->with('success', 'Lead berhasil ditambahkan!');
    }

    public function update(Request $request, Lead $lead)
    {
        $lead->update($this->validateLead($request, $lead));

        return redirect()->back()->with('success', 'Lead berhasil diperbarui!');
    }

    public function updateStage(Request $request, Lead $lead)
    {
        $request->validate([
            'pipeline_stage' => ['required', Rule::in(array_keys(Lead::STAGES))],
        ]);

        $lead->update(['pipeline_stage' => $request->pipeline_stage]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'stage' => $lead->pipeline_stage, 'label' => $lead->stageLabel()]);
        }

        return redirect()->back()->with('success', "Stage {$lead->business_name} diubah ke {$lead->stageLabel()}.");
    }

    public function storeNote(Request $request, Lead $lead)
    {
        $request->validate(['body' => 'required|string|max:5000']);

        $lead->notes()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
        ]);

        return redirect()->route('leads.show', $lead)->with('success', 'Catatan ditambahkan.');
    }

    public function destroyNote(Lead $lead, LeadNote $note)
    {
        abort_unless($note->lead_id === $lead->id, 404);

        $note->delete();

        return redirect()->route('leads.show', $lead)->with('success', 'Catatan dihapus.');
    }

    /**
     * Aksi massal dari tabel Leads: cari email dari website, audit website, atau ubah stage.
     * Crawl & audit berjalan di background sebagai satu batch; notifikasi muncul saat selesai.
     */
    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:crawl,audit,stage',
            'pipeline_stage' => ['required_if:action,stage', 'nullable', Rule::in(array_keys(Lead::STAGES))],
        ]);

        $ids = LeadSelection::ids($request);

        if (! $ids) {
            return redirect()->back()->with('error', 'Pilih minimal satu lead.');
        }

        if ($request->action === 'stage') {
            Lead::whereIn('id', $ids)->get()->each->update(['pipeline_stage' => $request->pipeline_stage]);

            return redirect()->back()->with('success', count($ids).' lead dipindah ke stage '.Lead::STAGES[$request->pipeline_stage].'.');
        }

        $query = Lead::whereIn('id', $ids)->whereNotNull('website')->where('website', '!=', '');
        if ($request->action === 'crawl') {
            // Hanya lead yang masih kekurangan email atau telepon
            $query->where(fn ($q) => $q->whereNull('email')->orWhere('email', '')->orWhereNull('phone')->orWhere('phone', ''));
        }

        $targets = $query->pluck('id');

        if ($targets->isEmpty()) {
            return redirect()->back()->with('error', $request->action === 'crawl'
                ? 'Tidak ada lead terpilih yang punya website dan masih kekurangan email/telepon.'
                : 'Tidak ada lead terpilih yang punya website.');
        }

        $action = $request->action;
        $label = $action === 'crawl' ? 'Cari email dari website' : 'Audit website';
        $total = $targets->count();

        Bus::batch($targets->map(fn ($id) => new EnrichLeadJob($id, $action))->all())
            ->name($label)
            ->allowFailures()
            ->finally(function () use ($label, $total) {
                ScrapingNotification::notify('success', "{$label} selesai", "{$label} untuk {$total} lead selesai diproses. Muat ulang halaman Leads untuk melihat hasilnya.");
            })
            ->onQueue($action === 'crawl' ? 'scraping' : 'default')
            ->dispatch();

        return redirect()->back()->with('success', "{$label} dijalankan di background untuk {$total} lead. Notifikasi muncul di ikon lonceng saat selesai.");
    }

    public function scrape(Request $request)
    {
        $request->validate([
            'niche' => 'required|string|max:100',
            'location' => 'required|string|max:100',
        ]);

        ScrapingNotification::notify(
            'running',
            'Scraping Sedang Dijalankan',
            "Scraping leads untuk niche \"{$request->niche}\" di kota \"{$request->location}\" sedang berjalan.",
            $request->niche,
            $request->location
        );

        ScrapeGoogleMapsJob::dispatch($request->niche, $request->location, $request->user()->id);

        return redirect()->route('leads.index')->with('success', 'Scraping dimulai di background. Proses ini bisa memakan beberapa menit; notifikasi akan muncul di ikon lonceng saat selesai.');
    }

    public function scrapeStatus()
    {
        return response()->json([
            'notifications' => ScrapingNotification::latest()->limit(30)->get(),
            'pending_scrapes' => $this->pendingScrapes(),
        ]);
    }

    public function markNotificationsRead()
    {
        ScrapingNotification::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function clearNotifications()
    {
        ScrapingNotification::query()->delete();

        return response()->json(['success' => true]);
    }

    public function crawlWebsite(Lead $lead, WebsiteCrawlerService $crawler)
    {
        if (empty($lead->website)) {
            return response()->json([
                'success' => false,
                'message' => 'Lead ini belum punya URL website.',
            ], 400);
        }

        // Crawl bisa sampai ~60 detik; jangan sampai kena max_execution_time PHP.
        set_time_limit((int) config('leadhunter.crawler.timeout', 60) + 30);

        try {
            $contacts = $crawler->crawl($lead->website);
        } catch (CrawlException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $updates = [];
        if ($contacts['email'] && empty($lead->email)) {
            $updates['email'] = $contacts['email'];
        }
        if ($contacts['phone'] && empty($lead->phone)) {
            $updates['phone'] = $contacts['phone'];
        }

        if ($updates) {
            $lead->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => $updates
                ? 'Kontak baru berhasil ditemukan dan disimpan!'
                : 'Crawl selesai, tetapi tidak ada email atau telepon baru yang ditemukan.',
            'data' => [
                'email' => $lead->email,
                'phone' => $lead->phone,
            ],
        ]);
    }

    public function destroy(Lead $lead)
    {
        $lead->outreachMessages()->delete();
        $lead->delete(); // catatan lead ikut terhapus (cascade)

        return redirect()->route('leads.index')->with('success', 'Lead berhasil dihapus!');
    }

    protected function validateLead(Request $request, ?Lead $lead = null): array
    {
        $request->merge(['website' => Url::normalize($request->input('website')) ?? ($request->filled('website') ? 'invalid' : null)]);

        return $request->validate([
            'business_name' => [
                'required', 'string', 'max:255',
                Rule::unique('leads')->where('city', $request->input('city'))->ignore($lead?->id),
            ],
            'niche' => 'required|string|max:255',
            'website' => 'nullable|url:http,https|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'source' => 'required|string|max:255',
        ], [
            'business_name.unique' => 'Lead dengan nama bisnis dan kota yang sama sudah ada.',
            'website.url' => 'Website harus berupa URL http/https yang valid.',
        ]);
    }

    protected function pendingScrapes(): int
    {
        return DB::table('jobs')
            ->where(fn ($q) => $q->where('queue', 'scraping')->orWhere('payload', 'like', '%ScrapeGoogleMapsJob%'))
            ->count();
    }
}
