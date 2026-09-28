<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

/**
 * Papan pipeline sederhana: new → contacted → replied → meeting → deal (atau lost).
 */
class PipelineController extends Controller
{
    public function index(Request $request)
    {
        $stages = Lead::STAGES;
        $perStage = 50;
        $filters = $request->only(['search', 'niche', 'city']);

        $base = Lead::query()->filter($filters);

        $counts = (clone $base)->selectRaw('pipeline_stage, COUNT(*) as total')
            ->groupBy('pipeline_stage')
            ->pluck('total', 'pipeline_stage');

        $columns = [];
        foreach ($stages as $stage => $label) {
            $columns[$stage] = [
                'label' => $label,
                'total' => (int) ($counts[$stage] ?? 0),
                // "Baru" biasanya ratusan lead hasil scraping; tampilkan sebagian saja.
                'leads' => (clone $base)->where('pipeline_stage', $stage)
                    ->withCount(['notes', 'outreachMessages'])
                    ->latest('updated_at')
                    ->limit($perStage)
                    ->get(),
            ];
        }

        $totalFound = array_sum(array_column($columns, 'total'));

        // Pencarian langsung: hanya papan yang dirender ulang
        if ($request->boolean('partial')) {
            return view('pipeline.partials.board', compact('columns', 'stages', 'perStage', 'totalFound', 'filters'));
        }

        $niches = Lead::whereNotNull('niche')->where('niche', '!=', '')->distinct()->orderBy('niche')->pluck('niche');
        $cities = Lead::whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city');

        return view('pipeline.index', compact('columns', 'stages', 'perStage', 'totalFound', 'filters', 'niches', 'cities'));
    }
}
