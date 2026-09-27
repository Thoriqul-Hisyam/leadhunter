<?php

namespace App\Services;

use App\Exceptions\AiException;
use App\Models\Lead;
use App\Models\OutreachMessage;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Satu-satunya tempat aplikasi berbicara dengan LLM (OpenAI-compatible API:
 * Groq, 9router, OpenRouter, dll) dan satu-satunya tempat prompt outreach dibuat.
 */
class AiService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected int $retries;

    public function __construct()
    {
        $this->apiKey = config('services.ai.key') ?: null;
        $this->baseUrl = rtrim((string) config('services.ai.base_url'), '/');
        $this->model = (string) config('services.ai.model');
        $this->timeout = (int) config('services.ai.timeout', 120);
        $this->retries = (int) config('services.ai.retries', 2);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Kirim prompt ke LLM dan kembalikan teks jawabannya.
     *
     * @throws AiException
     */
    public function generateText(string $prompt, array $options = []): string
    {
        $this->ensureConfigured();

        try {
            $response = $this->request()
                ->retry(
                    $this->retries + 1,
                    fn (int $attempt) => $attempt * 2000,
                    fn ($exception) => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && in_array($exception->response->status(), [429, 500, 502, 503, 504])),
                    throw: false
                )
                ->post($this->endpoint(), $this->payload($prompt, $options));
        } catch (ConnectionException $e) {
            throw new AiException('Tidak bisa terhubung ke AI API: '.$e->getMessage(), 0, $e);
        }

        return $this->extractContent($response);
    }

    /**
     * Kirim banyak prompt sekaligus secara paralel (dipakai preview composer, supaya
     * 10 lead tidak butuh 10x waktu satu panggilan AI).
     *
     * @param  array<string|int, string>  $prompts
     * @return array<string|int, string|AiException> teks jawaban, atau exception per prompt yang gagal
     */
    public function generateMany(array $prompts, array $options = [], int $concurrency = 5): array
    {
        if (! $this->isConfigured()) {
            return array_map(fn () => $this->notConfiguredException(), $prompts);
        }

        $keys = array_keys($prompts);

        $responses = Http::pool(function (Pool $pool) use ($prompts, $options) {
            $requests = [];
            foreach ($prompts as $key => $prompt) {
                $requests[] = $pool->as((string) $key)
                    ->withToken($this->apiKey)
                    ->acceptJson()
                    ->timeout($this->timeout)
                    ->post($this->endpoint(), $this->payload($prompt, $options));
            }

            return $requests;
        }, $concurrency);

        $results = [];
        foreach ($keys as $key) {
            $response = $responses[(string) $key] ?? null;

            try {
                if (! $response instanceof Response) {
                    throw new AiException('Tidak bisa terhubung ke AI API'.($response instanceof \Throwable ? ': '.$response->getMessage() : '.'));
                }

                $results[$key] = $this->extractContent($response);
            } catch (AiException $e) {
                $results[$key] = $e;
            }
        }

        return $results;
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw $this->notConfiguredException();
        }
    }

    protected function notConfiguredException(): AiException
    {
        return new AiException('AI belum dikonfigurasi. Isi AI_API_KEY (serta AI_BASE_URL dan AI_MODEL) di file .env.');
    }

    protected function endpoint(): string
    {
        return $this->baseUrl.'/chat/completions';
    }

    protected function request(): PendingRequest
    {
        return Http::withToken($this->apiKey)->acceptJson()->timeout($this->timeout);
    }

    protected function payload(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'stream' => false,
            'messages' => [
                ['role' => 'system', 'content' => $options['system'] ?? 'Anda adalah copywriter B2B berpengalaman untuk pasar Indonesia. Tulisan Anda singkat, natural, personal, dan tidak terdengar seperti spam.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];
    }

    /**
     * @throws AiException
     */
    protected function extractContent(Response $response): string
    {
        if ($response->failed()) {
            throw new AiException("AI API error ({$response->status()}): ".Str::limit($response->body(), 300));
        }

        $content = $response->json('choices.0.message.content');

        // Beberapa router tetap mengirim Server-Sent Events walau stream=false.
        if (! is_string($content) || trim($content) === '') {
            $content = $this->parseStreamedBody($response->body());
        }

        if (trim((string) $content) === '') {
            throw new AiException('AI mengembalikan respons kosong.');
        }

        return trim($content);
    }

    /*
    |--------------------------------------------------------------------------
    | Outreach
    |--------------------------------------------------------------------------
    */

    /**
     * Generate pesan outreach baru untuk satu lead.
     *
     * @param  array  $context  offer, sender, website, tone (formal|casual|friendly), language (id|en), instruction
     * @return array{subject: ?string, message: string}
     */
    public function generateOutreach(Lead $lead, string $channel, array $context = []): array
    {
        $raw = $this->generateText($this->outreachPrompt($lead, $channel, $context), ['temperature' => 0.8]);

        return $this->parseOutreach($raw, $lead, $channel, $context);
    }

    /**
     * Ubah jawaban mentah AI menjadi subjek + isi pesan yang sudah bertanda tangan.
     * Dipakai juga oleh preview composer (yang memanggil AI secara paralel).
     *
     * @return array{subject: ?string, message: string}
     */
    public function parseOutreach(string $raw, Lead $lead, string $channel, array $context = []): array
    {
        $subject = null;
        $raw = preg_replace('/^\s*(berikut( ini)?( adalah| merupakan)?|here is|here\'s)[^\n]*:\s*\n+/iu', '', $raw);

        // Baris pertama "SUBJEK: ..." (hanya diminta untuk email)
        if (preg_match('/^\s*\**(?:subjek|subject)\**\s*:\s*(.+?)\s*(?:\n|$)/iu', $raw, $m)) {
            $subject = trim($m[1], " \t\"'*");
            $raw = substr($raw, strlen($m[0]));
        }

        $body = $this->stripSignature($this->cleanMessage($raw), $context);

        return [
            'subject' => $channel === 'email' ? ($subject ?: $this->defaultSubject($lead)) : null,
            'message' => $body."\n\n".$this->signature($channel, $context),
        ];
    }

    public function outreachPrompt(Lead $lead, string $channel, array $context = []): string
    {
        $ctx = $this->context($context);
        $isEmail = $channel === 'email';
        $name = $lead->displayName();
        $kind = $lead->category ?: $lead->niche ?: 'bisnis';
        $company = Setting::get('company_name');

        $length = $isEmail
            ? '50–90 kata, 2–3 paragraf pendek'
            : '30–60 kata, 1–2 paragraf pendek, seperti chat WhatsApp biasa';

        $prompt = "Tulis ".($isEmail ? 'email' : 'pesan WhatsApp')." perkenalan pertama (cold outreach) dari {$ctx['sender']}"
            ." kepada {$name}, sebuah {$kind}".($lead->city ? " di {$lead->city}" : '').". Bahasa: {$ctx['language_label']}.\n\n"
            ."Yang diketahui tentang mereka:\n{$this->leadProfile($lead)}\n\n"
            ."Yang ditawarkan: {$ctx['offer']}".($company ? " (dari {$company})" : '')."\n"
            ."Sudut yang paling relevan: {$this->angle($lead, $ctx['offer'])}\n\n"
            ."Cara menulis:\n"
            ."- Tulis seperti orang sungguhan yang menulis pesan ini khusus untuk mereka, bukan template massal. Nada {$ctx['tone_label']}.\n"
            ."- Panjang isi {$length}.\n"
            ."- Buka dengan sapaan singkat ({$ctx['greeting']}), lalu satu kalimat yang relevan dengan bisnis mereka dan langsung ke alasan menghubungi.\n"
            ."- Sampaikan satu manfaat konkret untuk bisnis mereka, bukan daftar fitur.\n"
            ."- Tutup dengan satu pertanyaan ringan yang mudah dijawab ya/tidak, misalnya menawarkan contoh tampilan atau gambaran singkat. Jangan langsung meminta meeting.\n"
            ."- Jangan menyebut angka rating, jumlah ulasan, alamat, atau URL website mereka.\n"
            ."- Hindari pujian berlebihan (\"Selamat atas...\", \"reputasi sangat kuat\", \"sangat mengagumi\"), jargon (\"konversi\", \"optimalkan\", \"solusi digital\", \"kehadiran digital\", \"era digital\", \"meningkatkan kredibilitas\"), pembuka klise (\"Semoga email ini...\", \"Perkenalkan, saya...\" di kalimat pertama), tanda seru, emoji, markdown, dan placeholder seperti [Nama].\n"
            ."- Jangan menulis salam penutup atau tanda tangan; itu ditambahkan otomatis.\n\n"
            ."Format jawaban:\n"
            .($isEmail ? "SUBJEK: <3–6 kata, huruf kecil kecuali nama, tanpa tanda seru>\n\n" : '')
            ."<isi pesan, diawali sapaan>\n\n"
            ."Contoh gaya untuk bisnis lain (jangan disalin, hanya acuan nada dan panjang):\n"
            .($isEmail
                ? "SUBJEK: website untuk kopi senja?\n\nSelamat siang, tim Kopi Senja.\n\nKopi Senja cukup sering muncul di Google Maps saat orang mencari kafe di Denpasar, tapi belum ada website yang bisa dibuka untuk melihat menu atau reservasi.\n\nKami biasa membuatkan website sederhana untuk kafe: menu, jam buka, dan tombol reservasi lewat WhatsApp, supaya pengunjung baru lebih mudah memutuskan datang.\n\nKalau berkenan, boleh saya kirimkan contoh tampilannya untuk Kopi Senja?"
                : "Halo Kak, selamat siang. Saya lihat Kopi Senja belum punya website untuk menu dan reservasi, padahal cukup sering dicari di Google Maps.\n\nKami bantu kafe bikin website sederhana dengan tombol pesan via WhatsApp. Boleh saya kirim contoh tampilannya?");

        if (! empty($ctx['instruction'])) {
            $prompt .= "\n\nInstruksi tambahan dari pengguna (prioritaskan di atas aturan lain):\n{$ctx['instruction']}";
        }

        return $prompt;
    }

    /**
     * Buat Message Template (bisa dipakai ulang) untuk satu niche, lengkap dengan placeholder.
     *
     * @return array{subject: ?string, body: string}
     *
     * @throws AiException
     */
    public function generateTemplate(string $channel, string $niche, string $tone = 'formal', string $language = 'id', ?string $offer = null, ?string $instruction = null): array
    {
        $ctx = $this->context(['tone' => $tone, 'language' => $language, 'offer' => $offer, 'instruction' => $instruction]);
        $isEmail = $channel === 'email';

        $prompt = 'Buat template '.($isEmail ? 'email' : 'pesan WhatsApp')." cold outreach yang bisa dipakai ulang untuk banyak bisnis di bidang \"{$niche}\". Bahasa: {$ctx['language_label']}.\n\n"
            ."Yang ditawarkan: {$ctx['offer']}\n\n"
            ."Wajib memakai placeholder berikut persis seperti ini (dengan kurung kurawal ganda), karena akan diganti otomatis per lead:\n"
            ."- {{business_name}} = nama bisnis penerima (pakai 1–2 kali)\n"
            ."- {{city}} = kota penerima (opsional)\n"
            ."- {{offer}} = penawaran (opsional, boleh ditulis dengan kata sendiri)\n"
            ."Jangan membuat placeholder lain, jangan memakai [kurung siku], dan jangan menulis nama bisnis atau kota sungguhan.\n\n"
            ."Cara menulis:\n"
            ."- Nada {$ctx['tone_label']}. Tulis seperti orang sungguhan, bukan surat dinas atau iklan.\n"
            .'- Panjang isi '.($isEmail ? '50–90 kata, 2–3 paragraf pendek' : '30–60 kata, 1–2 paragraf pendek').".\n"
            ."- Buka dengan sapaan singkat ({$ctx['greeting']}), sebutkan satu kebutuhan yang umum di bidang {$niche}, lalu satu manfaat konkret dari penawaran.\n"
            ."- Template dikirim ke banyak bisnis, jadi jangan berasumsi penerima sudah atau belum punya website, dan jangan mengklaim tahu kondisi bisnis mereka.\n"
            ."- Tutup dengan satu pertanyaan ringan yang mudah dijawab (misalnya menawarkan contoh). Jangan langsung meminta meeting.\n"
            ."- Hindari pujian berlebihan, jargon (\"konversi\", \"optimalkan\", \"solusi digital\", \"kehadiran digital\", \"era digital\"), pembuka klise, tanda seru, emoji, dan markdown.\n"
            ."- Jangan menulis salam penutup atau tanda tangan; itu ditambahkan otomatis.\n\n"
            ."Format jawaban:\n"
            .($isEmail ? "SUBJEK: <3–6 kata, huruf kecil kecuali placeholder, tanpa tanda seru>\n\n" : '')
            .'<isi template>';

        if ($ctx['instruction'] !== '') {
            $prompt .= "\n\nInstruksi tambahan dari pengguna (prioritaskan):\n{$ctx['instruction']}";
        }

        $raw = preg_replace('/^\s*(berikut( ini)?( adalah| merupakan)?|here is|here\'s)[^\n]*:\s*\n+/iu', '', $this->generateText($prompt, ['temperature' => 0.8]));

        $subject = null;
        if (preg_match('/^\s*\**(?:subjek|subject)\**\s*:\s*(.+?)\s*(?:\n|$)/iu', $raw, $m)) {
            $subject = trim($m[1], " \t\"'*");
            $raw = substr($raw, strlen($m[0]));
        }

        // Rapikan variasi penulisan placeholder dari AI: {{ Business_Name }} → {{business_name}}
        $normalize = fn (string $text) => preg_replace_callback('/\{\{\s*([a-zA-Z_]+)\s*\}\}/', fn ($p) => '{{'.strtolower($p[1]).'}}', $text);

        $body = $this->stripSignature($this->cleanMessage($raw));
        $closing = $language === 'en' ? 'Best regards,' : 'Salam,';

        return [
            'subject' => $isEmail ? $normalize($subject ?: 'soal website {{business_name}}') : null,
            'body' => $normalize($body)."\n\n".($isEmail ? "{$closing}\n{{sender_name}}" : '{{sender_name}}'),
        ];
    }

    /**
     * Sudut penawaran berdasarkan kondisi website lead (hanya jika penawarannya soal website).
     */
    protected function angle(Lead $lead, string $offer): string
    {
        if (! str_contains(strtolower($offer), 'web')) {
            return 'Hubungkan penawaran dengan kebutuhan nyata bisnis sejenis; jangan mengarang masalah yang tidak diketahui.';
        }

        if (! $lead->website) {
            return 'Mereka belum punya website, jadi calon pelanggan yang mencari di Google hanya menemukan profil Google Maps. '
                .'Tunjukkan bagaimana website sederhana membantu (informasi layanan/harga, lokasi, tombol booking atau chat WhatsApp).';
        }

        if ($platform = $this->socialPlatform($lead->website)) {
            return "Mereka memakai {$platform} sebagai pengganti website. Tunjukkan manfaat punya website sendiri yang mudah ditemukan di Google, "
                ."tanpa meremehkan {$platform} mereka.";
        }

        return 'Mereka sudah punya website. Jangan menyiratkan websitenya buruk dan jangan mengaku sudah menilainya. '
            .'Tawarkan bantuan yang spesifik, misalnya tampilan mobile yang lebih cepat, halaman layanan yang lebih jelas, atau booking online, sebagai tawaran, bukan kritik.';
    }

    protected function socialPlatform(string $url): ?string
    {
        foreach (['instagram.com' => 'Instagram', 'facebook.com' => 'Facebook', 'tiktok.com' => 'TikTok', 'linktr.ee' => 'Linktree', 'wa.me' => 'WhatsApp', 'shopee' => 'Shopee', 'tokopedia' => 'Tokopedia'] as $domain => $name) {
            if (str_contains(strtolower($url), $domain)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Tanda tangan ditambahkan oleh kode (bukan AI) agar konsisten dan tidak dobel.
     */
    public function signature(string $channel, array $context = []): string
    {
        $custom = trim((string) ($context['sender'] ?? ''));
        $language = ($context['language'] ?? 'id') === 'en' ? 'en' : 'id';
        $closing = $language === 'en' ? 'Best regards,' : 'Salam,';

        // Identitas diketik manual di composer (mis. "Ahmad dari Lefateach")
        if ($custom !== '' && $custom !== Setting::senderIdentity()) {
            return "{$closing}\n{$custom}";
        }

        $name = trim((string) Setting::get('sender_name'));
        $company = trim((string) Setting::get('company_name'));
        $website = trim((string) Setting::get('company_website'));

        if ($channel === 'whatsapp') {
            return trim($name.($company ? " · {$company}" : '')) ?: Setting::senderIdentity();
        }

        $lines = array_filter([$name ?: Setting::senderIdentity(), implode(' · ', array_filter([$company, $website]))]);

        return $closing."\n".implode("\n", $lines);
    }

    /**
     * Buang salam penutup/tanda tangan yang tetap ditulis AI walau sudah dilarang.
     */
    protected function stripSignature(string $text, array $context = []): string
    {
        $names = array_filter([
            Setting::get('sender_name'),
            Setting::get('company_name'),
            $context['sender'] ?? null,
        ]);

        $lines = preg_split('/\r?\n/', rtrim($text));

        while ($lines) {
            $last = trim(end($lines));
            $isClosing = $last === ''
                || preg_match('/^(salam|hormat( saya| kami)?|terima kasih|best( regards)?|regards|sincerely|salam hangat|wassalam)[\s,.!]*$/iu', $last)
                || collect($names)->contains(fn ($n) => mb_strlen($last) <= mb_strlen($n) + 20 && str_contains(mb_strtolower($last), mb_strtolower($n)));

            if (! $isClosing || count($lines) <= 1) {
                break;
            }

            array_pop($lines);
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Mode hybrid: poles draf template yang sudah dirender agar lebih personal.
     */
    public function polishDraft(Lead $lead, string $draft, array $context = []): string
    {
        return $this->cleanMessage($this->generateText($this->polishDraftPrompt($lead, $draft, $context)));
    }

    public function polishDraftPrompt(Lead $lead, string $draft, array $context = []): string
    {
        $ctx = $this->context($context);

        return "Poles draf pesan outreach B2B berikut agar terasa lebih natural dan relevan untuk bisnis target, tanpa mengubah maksudnya.\n\n"
            ."Pertahankan:\n"
            ."1. Nada: {$ctx['tone_label']}\n"
            ."2. Call to action asli\n"
            ."3. Nama pengirim asli: {$ctx['sender']}\n"
            ."4. Bahasa: {$ctx['language_label']}\n\n"
            ."Draf:\n\"\"\"\n{$draft}\n\"\"\"\n\n"
            ."Data bisnis target:\n{$this->leadProfile($lead)}\n\n"
            ."Kembalikan HANYA isi pesan yang sudah dipoles, tanpa markdown dan tanpa kalimat pengantar atau penjelasan.";
    }

    /**
     * Poles pesan berdasarkan instruksi bebas dari pengguna.
     */
    public function polishWithInstruction(Lead $lead, string $channel, string $message, string $instruction): string
    {
        $prompt = "Berikut draf pesan outreach {$channel} untuk {$lead->business_name} ({$lead->niche}, {$lead->city}).\n\n"
            ."\"\"\"\n{$message}\n\"\"\"\n\n"
            ."Ubah draf di atas sesuai instruksi ini:\n\"{$instruction}\"\n\n"
            ."Aturan: kembalikan HANYA isi pesan hasil revisi, tanpa markdown, tanpa kalimat pengantar atau penjelasan. Pertahankan nama pengirim dan call to action yang penting.";

        return $this->cleanMessage($this->generateText($prompt));
    }

    /**
     * Pesan follow-up singkat untuk lead yang belum membalas.
     */
    public function generateFollowup(Lead $lead, OutreachMessage $original, array $context = []): string
    {
        $ctx = $this->context($context);
        $days = $original->sent_at ? max(1, (int) $original->sent_at->diffInDays(now())) : null;
        $channelLabel = $original->type === 'whatsapp' ? 'pesan WhatsApp' : 'email';

        $prompt = "Tulis {$channelLabel} follow-up yang sangat singkat (2–3 kalimat, di bawah 50 kata) dalam {$ctx['language_label']} untuk {$lead->displayName()}"
            .($days ? ", karena pesan pertama {$days} hari lalu belum dibalas" : ', karena pesan pertama belum dibalas').".\n\n"
            ."Pesan pertama:\n\"\"\"\n{$this->stripSignature($original->message)}\n\"\"\"\n\n"
            ."Aturan: sapaan singkat ({$ctx['greeting']}), sopan, tidak memaksa, tidak menyalahkan, tidak mengulang isi pesan pertama. "
            ."Ingatkan penawaran dalam satu kalimat, lalu satu pertanyaan ya/tidak yang mudah dijawab. "
            .'Jangan menulis salam penutup atau tanda tangan (ditambahkan otomatis). Tanpa markdown, tanpa subjek, tanpa kalimat pengantar, tanpa tanda seru.';

        $body = $this->stripSignature($this->cleanMessage($this->generateText($prompt, ['temperature' => 0.8])), $context);

        return $body."\n\n".$this->signature($original->type, $context);
    }

    /*
    |--------------------------------------------------------------------------
    | Smart matching
    |--------------------------------------------------------------------------
    */

    /**
     * Pilih ID lead yang relevan dengan niche & lokasi campaign.
     *
     * @param  array<int, array{id: int, name: string, niche: ?string, city: ?string}>  $candidates
     * @return array<int, int>
     *
     * @throws AiException
     */
    public function matchLeadIds(string $niche, string $location, array $candidates): array
    {
        $prompt = "Sebuah campaign dibuat untuk niche \"{$niche}\" di lokasi \"{$location}\".\n"
            ."Pilih kandidat lead yang benar-benar relevan dengan niche dan lokasi tersebut.\n"
            ."Jawab HANYA dengan JSON mentah berbentuk {\"ids\":[1,3,5]} tanpa markdown atau teks lain.\n\n"
            ."Kandidat:\n".json_encode($candidates, JSON_UNESCAPED_UNICODE);

        $response = $this->generateText($prompt, ['temperature' => 0.1]);

        $json = preg_match('/\{.*\}/s', $response, $m) ? $m[0] : $response;
        $data = json_decode($json, true);

        if (! is_array($data) || ! isset($data['ids']) || ! is_array($data['ids'])) {
            throw new AiException('Format JSON dari AI tidak valid.');
        }

        $allowed = array_column($candidates, 'id');

        return array_values(array_intersect(array_map('intval', $data['ids']), $allowed));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function defaultSubject(Lead $lead): string
    {
        return $lead->website && ! $this->socialPlatform($lead->website)
            ? 'soal website '.$lead->displayName()
            : 'website untuk '.$lead->displayName().'?';
    }

    /**
     * Fakta tentang lead untuk personalisasi prompt. Sengaja kualitatif (tanpa angka rating,
     * jumlah ulasan, alamat, atau URL) supaya AI tidak "membacakan data" seperti robot.
     */
    public function leadProfile(Lead $lead): string
    {
        $lines = ["- Nama: {$lead->displayName()}"];

        if ($lead->category || $lead->niche) {
            $lines[] = '- Bidang: '.($lead->category ?: $lead->niche);
        }
        if ($lead->city) {
            $lines[] = "- Kota: {$lead->city}";
        }

        if ($lead->rating >= 4.5 && $lead->reviews_count >= 200) {
            $lines[] = '- Di Google Maps: sangat populer, banyak ulasan positif';
        } elseif ($lead->rating >= 4.5 && $lead->reviews_count >= 20) {
            $lines[] = '- Di Google Maps: ulasannya bagus';
        } elseif ($lead->reviews_count && $lead->reviews_count < 20) {
            $lines[] = '- Di Google Maps: masih sedikit ulasan (bisnis yang relatif baru dikenal)';
        }

        if (! $lead->website) {
            $lines[] = '- Website: belum punya';
        } elseif ($platform = $this->socialPlatform($lead->website)) {
            $lines[] = "- Website: belum punya sendiri, memakai {$platform}";
        } else {
            $lines[] = '- Website: sudah punya';
        }

        return implode("\n", $lines);
    }

    /**
     * Bersihkan artefak umum dari output LLM (markdown, kalimat pengantar, baris subjek).
     */
    public function cleanMessage(string $text): string
    {
        $text = str_replace(['**', '__'], '', $text);
        // Catatan meta yang kadang ikut dari router/system prompt pihak ketiga, mis. "→ skipped: [...]"
        $text = preg_replace('/^\s*(→|->|=>)?\s*(skipped|note|catatan|nb)\s*:.*$\R?/imu', '', $text);
        $text = preg_replace('/^\s*(→|->|=>)\s.*$\R?/mu', '', $text);
        $text = preg_replace('/^\s*(berikut( ini)?( adalah| merupakan)?|here is|here\'s)[^\n]*:\s*\n+/iu', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/^\s*(subjek|subject)\s*:[^\n]*\n+/iu', '', $text);
        $text = preg_replace('/^\s*"""\s*|\s*"""\s*$/u', '', $text);

        return trim($text);
    }

    protected function context(array $context): array
    {
        $language = ($context['language'] ?? 'id') === 'en' ? 'en' : 'id';
        $tone = in_array($context['tone'] ?? null, ['formal', 'casual', 'friendly'], true) ? $context['tone'] : 'formal';

        return [
            'offer' => ($context['offer'] ?? null) ?: Setting::defaultOffer(),
            'sender' => ($context['sender'] ?? null) ?: Setting::senderIdentity(),
            'website' => array_key_exists('website', $context) ? $context['website'] : Setting::get('company_website'),
            'tone' => $tone,
            'tone_label' => [
                'formal' => 'sopan dan profesional, tapi tetap terdengar seperti orang sungguhan, bukan surat dinas',
                'casual' => 'santai namun tetap sopan',
                'friendly' => 'hangat dan bersahabat',
            ][$tone],
            'greeting' => $language === 'en'
                ? 'e.g. "Hi <name> team,"'
                : [
                    'formal' => 'misalnya "Selamat siang, Bapak/Ibu pengelola <nama bisnis>." atau "Selamat siang, tim <nama bisnis>."; jangan "Yth. Pimpinan"',
                    'casual' => 'misalnya "Halo Kak," atau "Halo tim <nama bisnis>,"',
                    'friendly' => 'misalnya "Halo tim <nama bisnis>,"',
                ][$tone],
            'language_label' => $language === 'en' ? 'English' : 'Bahasa Indonesia',
            'instruction' => trim((string) ($context['instruction'] ?? '')),
        ];
    }

    protected function parseStreamedBody(string $body): string
    {
        $content = '';

        foreach (preg_split('/\r?\n/', $body) as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $chunk = json_decode(trim(substr($line, 5)), true);
            $content .= $chunk['choices'][0]['delta']['content'] ?? $chunk['choices'][0]['message']['content'] ?? '';
        }

        return $content;
    }
}
