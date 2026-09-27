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

        $base = Lead::query()->filter($request->only(['search', 'niche', 'city']));

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

        return view('pipeline.index', compact('columns', 'stages', 'perStage'));
    }
}
