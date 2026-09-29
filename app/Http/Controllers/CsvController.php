<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Services\Scraping\LeadScraperService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export/Import CSV untuk leads dan hasil outreach.
 */
class CsvController extends Controller
{
    protected const LEAD_COLUMNS = [
        'business_name', 'niche', 'category', 'city', 'address', 'email', 'phone', 'website',
        'rating', 'reviews_count', 'pipeline_stage', 'source', 'google_maps_url', 'created_at',
    ];

    /**
     * Nama kolom alternatif yang dikenali saat import.
     */
    protected const ALIASES = [
        'business_name' => ['business_name', 'name', 'nama', 'nama_bisnis', 'title'],
        'niche' => ['niche', 'bidang'],
        'category' => ['category', 'kategori', 'categoryname'],
        'city' => ['city', 'kota', 'location', 'lokasi'],
        'address' => ['address', 'alamat'],
        'email' => ['email', 'e-mail', 'emails'],
        'phone' => ['phone', 'telepon', 'telp', 'no_hp', 'whatsapp', 'wa'],
        'website' => ['website', 'web', 'url', 'situs'],
        'rating' => ['rating', 'totalscore'],
        'reviews_count' => ['reviews_count', 'reviews', 'ulasan', 'reviewscount'],
        'google_maps_url' => ['google_maps_url', 'maps_url', 'gmaps'],
    ];

    public function exportLeads(Request $request): StreamedResponse
    {
        $query = Lead::query()
            ->filter($request->only(['search', 'no_website', 'has_email', 'has_phone', 'has_website', 'stage']))
            ->orderBy('id');

        return $this->stream('leads-'.now()->format('Ymd-His').'.csv', self::LEAD_COLUMNS, function ($out) use ($query) {
            $query->chunk(500, function ($leads) use ($out) {
                foreach ($leads as $lead) {
                    fputcsv($out, array_map(fn ($col) => (string) $lead->{$col}, self::LEAD_COLUMNS));
                }
            });
        });
    }

    public function exportOutreach(Request $request): StreamedResponse
    {
        $columns = ['id', 'campaign', 'business_name', 'email', 'phone', 'type', 'mode', 'status', 'subject', 'message', 'sent_at', 'replied_at', 'created_at'];

        $query = OutreachMessage::with(['lead', 'campaign'])->orderBy('id');
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if (in_array($request->type, ['email', 'whatsapp'], true)) {
            $query->where('type', $request->type);
        }

        return $this->stream('outreach-'.now()->format('Ymd-His').'.csv', $columns, function ($out) use ($query) {
            $query->chunk(500, function ($messages) use ($out) {
                foreach ($messages as $m) {
                    fputcsv($out, [
                        $m->id, $m->campaign?->name, $m->lead?->business_name, $m->lead?->email, $m->lead?->phone,
                        $m->type, $m->mode, $m->status, $m->subject, $m->message,
                        $m->sent_at, $m->replied_at, $m->created_at,
                    ]);
                }
            });
        });
    }

    public function importLeads(Request $request, LeadScraperService $saver)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'default_niche' => 'nullable|string|max:255',
            'default_city' => 'nullable|string|max:255',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $firstLine = (string) fgets($handle);
        rewind($handle);

        // Excel dengan locale Indonesia menyimpan CSV dengan pemisah titik koma.
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if (! $header) {
            fclose($handle);

            return redirect()->back()->with('error', 'File CSV kosong.');
        }

        $map = $this->mapHeader($header);
        if (! isset($map['business_name'])) {
            fclose($handle);

            return redirect()->back()->with('error', 'Kolom nama bisnis tidak ditemukan. Gunakan header "business_name" atau "nama".');
        }

        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $get = fn (string $field) => isset($map[$field]) ? trim((string) ($row[$map[$field]] ?? '')) : '';

            $city = $get('city') ?: (string) $request->input('default_city');
            if ($get('business_name') === '' || $city === '') {
                $stats['skipped']++;

                continue;
            }

            [$outcome] = $saver->saveLead([
                'name' => $get('business_name'),
                'address' => $get('address'),
                'phone' => $get('phone'),
                'website' => $get('website'),
                'email' => $get('email'),
                'category' => $get('category'),
                'rating' => str_replace(',', '.', $get('rating')),
                'reviews_count' => preg_replace('/\D/', '', $get('reviews_count')),
                'google_maps_url' => $get('google_maps_url'),
            ], $get('niche') ?: ((string) $request->input('default_niche') ?: 'umum'), $city, 'csv_import');

            $stats[$outcome]++;
        }

        fclose($handle);

        return redirect()->route('leads.index')->with('success', "Import selesai: {$stats['created']} lead baru, {$stats['updated']} diperbarui, {$stats['unchanged']} sudah ada, {$stats['skipped']} baris dilewati.");
    }

    protected function mapHeader(array $header): array
    {
        $map = [];

        foreach ($header as $index => $name) {
            $normalized = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $name)));
            $normalized = str_replace([' ', '-'], '_', $normalized);

            foreach (self::ALIASES as $field => $aliases) {
                if (! isset($map[$field]) && in_array($normalized, array_map(fn ($a) => str_replace('-', '_', $a), $aliases), true)) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    protected function stream(string $filename, array $header, callable $writeRows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $writeRows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($out, $header);
            $writeRows($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
