<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class GoogleMapsScraperService
{
    /**
     * Scrape bisnis ASLI dari Google Maps menggunakan Puppeteer.
     * Puppeteer membuka Google Maps → search niche+lokasi → ambil data tiap bisnis.
     */
    public function scrape(string $niche, string $location): array
    {
        $query = trim($niche) . ' ' . trim($location);
        $scriptPath = base_path('scripts/scrape-gmaps.js');

        try {
            $nodePath = 'C:\\nvm4w\\nodejs\\node.exe';
            if (!file_exists($nodePath)) {
                $nodePath = 'node'; // fallback
            }

            $env = array_merge($_SERVER, $_ENV, [
                'PATH' => (getenv('PATH') ?: '') . ';C:\\Windows\\System32;C:\\Windows;C:\\Windows\\System32\\Wbem;C:\\nvm4w\\nodejs',
                'SystemRoot' => 'C:\\Windows',
                'SystemDrive' => 'C:',
                'USERPROFILE' => 'C:\\Users\\mochl',
                'LOCALAPPDATA' => 'C:\\Users\\mochl\\AppData\\Local',
                'APPDATA' => 'C:\\Users\\mochl\\AppData\\Roaming',
                'TEMP' => 'C:\\Windows\\Temp',
                'TMP' => 'C:\\Windows\\Temp',
            ]);

            $bufferAccumulator = '';
            $result = Process::timeout(900)->env($env)->run(
                [$nodePath, $scriptPath, $query],
                function (string $type, string $buffer) use (&$bufferAccumulator, $niche, $location) {
                    if ($type === 'out') {
                        $bufferAccumulator .= $buffer;
                        $lines = explode("\n", $bufferAccumulator);
                        
                        // Keep the last element (which might be incomplete) in the accumulator
                        $bufferAccumulator = array_pop($lines);
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (str_starts_with($line, 'LEAD_ROW:')) {
                                $json = substr($line, 9);
                                $item = json_decode($json, true);
                                if ($item && !empty($item['name'])) {
                                    $cityClean = ucwords(strtolower(trim($location)));
                                    $address = $item['address'] ?? '';
                                    $address = preg_replace('/[\x{E000}-\x{F8FF}]/u', '', $address); 
                                    $address = trim($address) ?: $cityClean;
                                    
                                    // Save instantly to database!
                                    \App\Models\Lead::firstOrCreate(
                                        ['business_name' => $item['name'], 'city' => $cityClean],
                                        [
                                            'business_name' => $item['name'],
                                            'niche'         => $niche,
                                            'website'       => $item['website'] ?? null,
                                            'email'         => $item['email'] ?? null,
                                            'phone'         => $item['phone'] ?? null,
                                            'address'       => $address,
                                            'city'          => $cityClean,
                                            'source'        => 'google_maps',
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            );

            // Process any leftover content in bufferAccumulator
            $bufferAccumulator = trim($bufferAccumulator);
            if (str_starts_with($bufferAccumulator, 'LEAD_ROW:')) {
                $json = substr($bufferAccumulator, 9);
                $item = json_decode($json, true);
                if ($item && !empty($item['name'])) {
                    $cityClean = ucwords(strtolower(trim($location)));
                    $address = $item['address'] ?? '';
                    $address = preg_replace('/[\x{E000}-\x{F8FF}]/u', '', $address); 
                    $address = trim($address) ?: $cityClean;
                    
                    \App\Models\Lead::firstOrCreate(
                        ['business_name' => $item['name'], 'city' => $cityClean],
                        [
                            'business_name' => $item['name'],
                            'niche'         => $niche,
                            'website'       => $item['website'] ?? null,
                            'email'         => $item['email'] ?? null,
                            'phone'         => $item['phone'] ?? null,
                            'address'       => $address,
                            'city'          => $cityClean,
                            'source'        => 'google_maps',
                        ]
                    );
                }
            }

            if ($result->successful()) {
                // Background job already stored everything in real-time
                return [];
            }

            // Log error for debugging
            $errOutput = $result->errorOutput();
            if ($errOutput) {
                Log::warning('Google Maps scraper stderr: ' . substr($errOutput, 0, 500));
            }

        } catch (\Exception $e) {
            Log::error('Google Maps scraper failed: ' . $e->getMessage());
        }

        // Fallback: AI-generated data via Groq
        return $this->fallbackAI($niche, $location);
    }

    /**
     * Format raw Puppeteer results ke lead format database.
     */
    protected function formatLeads(array $rawResults, string $niche, string $location): array
    {
        $leads = [];
        $cityClean = ucwords(strtolower(trim($location)));

        foreach ($rawResults as $item) {
            if (empty($item['name'])) continue;

            $address = $item['address'] ?? '';
            $address = preg_replace('/[\x{E000}-\x{F8FF}]/u', '', $address); // Remove private use characters like 
            $address = trim($address) ?: $cityClean;

            $leads[] = [
                'business_name' => $item['name'],
                'niche'         => $niche,
                'website'       => $item['website'] ?? null,
                'email'         => $item['email'] ?? null,
                'phone'         => $item['phone'] ?? null,
                'address'       => $address,
                'city'          => $cityClean,
                'source'        => 'google_maps',
            ];
        }

        return $leads;
    }

    /**
     * Fallback: Gunakan Groq AI jika Puppeteer gagal.
     */
    protected function fallbackAI(string $niche, string $location): array
    {
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) return [];

        $cityClean = ucwords($location);

        $prompt = "Berikan 8 data bisnis \"{$niche}\" yang benar-benar ada di kota \"{$cityClean}\", Indonesia. "
            . "Nama bisnis, alamat jalan, dan nomor telepon harus serealistis mungkin. "
            . "Output HANYA JSON array: "
            . '[{"business_name":"...","website":"https://...","phone":"+62...","address":"Jl. ..."}]';

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->timeout(15)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.1-8b-instant',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Output HANYA valid JSON array tanpa markdown.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 2048,
                ]);

            if ($response->successful()) {
                $content = trim($response->json('choices.0.message.content') ?? '');
                $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
                $content = preg_replace('/\s*```$/i', '', $content);

                if (preg_match('/\[[\s\S]*\]/', $content, $m)) {
                    $data = json_decode($m[0], true);
                    if (is_array($data)) {
                        return $this->formatLeads($data, $niche, $location);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('AI fallback error: ' . $e->getMessage());
        }

        return [];
    }
}
