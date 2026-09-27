<?php

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Models\MessageTemplate;
use App\Services\AiService;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MessageTemplate::latest();

        // Filters
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('niche')) {
            $search = trim($request->niche);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('niche', 'like', '%' . $search . '%')
                  ->orWhere('channel', 'like', '%' . $search . '%')
                  ->orWhere('language', 'like', '%' . $search . '%')
                  ->orWhere('tone', 'like', '%' . $search . '%')
                  ->orWhere('subject', 'like', '%' . $search . '%')
                  ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        $templates = $query->paginate(15)->appends($request->query());
        
        // Fetch all unique niches for filtering dropdown
        $niches = MessageTemplate::select('niche')->distinct()->pluck('niche')->toArray();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'html' => view('templates.partials.table', compact('templates'))->render(),
            ]);
        }

        return view('templates.index', compact('templates', 'niches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:email,whatsapp',
            'niche' => 'required|string|max:255',
            'language' => 'required|in:id,en',
            'tone' => 'required|in:formal,casual,friendly',
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['niche'] = strtolower(trim($data['niche']));
        $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;

        MessageTemplate::create($data);

        return redirect()->route('templates.index')->with('success', 'Template created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MessageTemplate $template)
    {
        return view('templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MessageTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:email,whatsapp',
            'niche' => 'required|string|max:255',
            'language' => 'required|in:id,en',
            'tone' => 'required|in:formal,casual,friendly',
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['niche'] = strtolower(trim($data['niche']));
        $data['is_active'] = $request->has('is_active');

        $template->update($data);

        return redirect()->route('templates.index')->with('success', 'Template updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MessageTemplate $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted successfully!');
    }

    /**
     * Tulis draf template dengan AI (dipanggil dari form create/edit, hasilnya diisi ke form).
     */
    public function generate(Request $request, AiService $ai)
    {
        $data = $request->validate([
            'channel' => 'required|in:email,whatsapp',
            'niche' => 'required|string|max:255',
            'language' => 'required|in:id,en',
            'tone' => 'required|in:formal,casual,friendly',
            'offer' => 'nullable|string|max:255',
            'instruction' => 'nullable|string|max:1000',
        ], [
            'niche.required' => 'Isi niche terlebih dahulu (misalnya: klinik gigi, cafe).',
        ]);

        set_time_limit(300);

        try {
            $result = $ai->generateTemplate(
                $data['channel'],
                $data['niche'],
                $data['tone'],
                $data['language'],
                $data['offer'] ?? null,
                $data['instruction'] ?? null
            );
        } catch (AiException $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal generate template: '.$e->getMessage()], 500);
        }

        return response()->json(['status' => 'success'] + $result);
    }

    /**
     * Toggle status via AJAX.
     */
    public function toggleStatus(MessageTemplate $template)
    {
        $template->update([
            'is_active' => !$template->is_active
        ]);

        return response()->json([
            'status' => 'success',
            'is_active' => $template->is_active,
            'message' => 'Template status updated!'
        ]);
    }
}
