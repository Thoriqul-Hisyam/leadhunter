<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use App\Services\GoogleMapsScraperService;

use App\Models\Campaign;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query();
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('business_name', 'like', '%' . $request->search . '%')
                  ->orWhere('niche', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('no_website')) {
            $query->where(function($q) {
                $q->whereNull('website')->orWhere('website', '');
            });
        }

        $pendingScrapes = \Illuminate\Support\Facades\DB::table('jobs')->count();
        $leads = $query->latest()->paginate(20);
        $campaigns = Campaign::all();
        return view('leads.index', compact('leads', 'pendingScrapes', 'campaigns'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'niche' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'source' => 'required|string|max:255',
        ]);

        Lead::create($validated);

        return redirect()->route('leads.index')->with('success', 'Lead created successfully!');
    }

    public function scrape(Request $request)
    {
        $request->validate([
            'niche' => 'required|string',
            'location' => 'required|string',
        ]);

        // Create 'running' status notification in database
        \App\Models\ScrapingNotification::create([
            'niche' => $request->niche,
            'location' => $request->location,
            'type' => 'running',
            'title' => 'Scraping Sedang Dijalankan',
            'message' => "Scraping leads untuk niche \"{$request->niche}\" di kota \"{$request->location}\" sedang berjalan.",
            'is_read' => false,
        ]);

        \App\Jobs\ScrapeGoogleMapsJob::dispatch($request->niche, $request->location);

        return redirect()->route('leads.index')->with('success', 'Scraping started in the background! It may take a few minutes to complete. Please check back later or refresh this page to see new leads.');
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'niche' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'source' => 'required|string|max:255',
        ]);

        $lead->update($validated);

        return redirect()->route('leads.index')->with('success', 'Lead updated successfully!');
    }

    public function scrapeStatus(Request $request)
    {
        // 1. Optional action to mark all notifications as read
        if ($request->query('action') === 'mark-read') {
            \App\Models\ScrapingNotification::where('is_read', false)->update(['is_read' => true]);
            return response()->json(['success' => true]);
        }

        // 2. Optional action to clear all notifications (delete from DB)
        if ($request->query('action') === 'clear') {
            \App\Models\ScrapingNotification::query()->delete();
            return response()->json(['success' => true]);
        }

        // 3. Return latest 30 scraping notifications from database
        $notifications = \App\Models\ScrapingNotification::latest()->limit(30)->get();

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    public function crawlWebsite(Request $request, Lead $lead)
    {
        if (empty($lead->website)) {
            return response()->json([
                'success' => false,
                'message' => 'Lead does not have a website URL registered.'
            ], 400);
        }

        $nodePath = 'C:\\nvm4w\\nodejs\\node.exe';
        if (!file_exists($nodePath)) {
            $nodePath = 'node';
        }
        $scriptPath = base_path('scripts/crawl-website.js');

        try {
            $processResult = \Illuminate\Support\Facades\Process::timeout(40)->run([$nodePath, $scriptPath, $lead->website]);
            
            if ($processResult->successful()) {
                $output = $processResult->output();
                $lines = explode("\n", $output);
                $crawlData = null;
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (str_starts_with($line, 'RESULT_JSON:')) {
                        $json = substr($line, 12);
                        $crawlData = json_decode($json, true);
                        break;
                    }
                }
                
                if ($crawlData && ($crawlData['success'] ?? false)) {
                    $updates = [];
                    if (!empty($crawlData['email']) && empty($lead->email)) {
                        $updates['email'] = $crawlData['email'];
                    }
                    if (!empty($crawlData['phone']) && empty($lead->phone)) {
                        $updates['phone'] = $crawlData['phone'];
                    }
                    
                    if (!empty($updates)) {
                        $lead->update($updates);
                        return response()->json([
                            'success' => true,
                            'message' => 'Successfully found and updated contact details!',
                            'data' => [
                                'email' => $lead->email,
                                'phone' => $lead->phone,
                            ]
                        ]);
                    } else {
                        return response()->json([
                            'success' => true,
                            'message' => 'Crawl completed, but no new email or phone details were found.',
                            'data' => [
                                'email' => $lead->email,
                                'phone' => $lead->phone,
                            ]
                        ]);
                    }
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Could not crawl the website or extract contact information. Please check if the website is online.'
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Crawling failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Lead $lead)
    {
        // Safe cascading delete of outreach messages in PHP
        $lead->outreachMessages()->delete();
        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead deleted successfully!');
    }
}
