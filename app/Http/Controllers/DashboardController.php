<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Campaign;
use App\Models\OutreachMessage;

class DashboardController extends Controller
{
    public function index()
    {
        $totalLeads = Lead::count();
        $totalCampaigns = Campaign::count();
        $outreachSent = OutreachMessage::where('status', 'sent')->count();
        $replied = OutreachMessage::where('status', 'replied')->count();
        $recentOutreach = OutreachMessage::with(['lead', 'campaign'])->latest()->take(5)->get();

        return view('dashboard', compact('totalLeads', 'totalCampaigns', 'outreachSent', 'replied', 'recentOutreach'));
    }
}
