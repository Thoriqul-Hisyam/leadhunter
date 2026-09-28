<?php

namespace App\Services;

use App\Exceptions\AiException;
use App\Helpers\Url;
use App\Models\AiUsageLog;
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
    /** Fitur yang cukup memakai model cepat (jawaban pendek, tidak perlu kualitas tulisan). */
    public const FAST_FEATURES = ['classify', 'match'];

    protected int $timeout;
    protected int $retries;

    /** @var array<int, array{name: string, base_url: string, key: string, model: string}> */
    protected array $providers = [];

    protected ?string $fastModel;

    public function __construct()
    {
        $this->timeout = (int) config('services.ai.timeout', 120);
        $this->retries = (int) config('services.ai.retries', 2);
        $this->fastModel = config('services.ai.fast_model') ?: null;

        $candidates = [
            ['name' => 'primary'] + (array) config('services.ai'),
            ['name' => 'backup'] + (array) config('services.ai.backup', []),
        ];

        foreach ($candidates as $provider) {
            if (! empty($provider['key']) && ! empty($provider['base_url']) && ! empty($provider['model'])) {
                $this->providers[] = [
                    'name' => $provider['name'],
                    'base_url' => rtrim((string) $provider['base_url'], '/'),
                    'key' => (string) $provider['key'],
                    'model' => (string) $provider['model'],
                ];
            }
        }
    }

    public function isConfigured(): bool
    {
        return $this->providers !== [];
    }

    public function hasBackup(): bool
    {
        return count($this->providers) > 1;
    }

    /**
     * Kirim prompt ke LLM dan kembalikan teks jawabannya. Jika provider utama gagal
     * (timeout, error server, rate limit), provider cadangan dicoba otomatis.
     *
     * @param  array  $options  feature, system, temperature, max_tokens
     *
     * @throws AiException
     */
    public function generateText(string $prompt, array $options = []): string
    {
        $this->ensureConfigured();

        $feature = $options['feature'] ?? 'other';
        $last = null;

        foreach ($this->providers as $provider) {
            $model = $this->modelFor($provider, $feature);
            $started = hrtime(true);

            try {
                $response = $this->request($provider)
                    ->retry(
                        $this->retries + 1,
                        fn (int $attempt) => $attempt * 2000,
                        fn ($exception) => $exception instanceof ConnectionException
                            || ($exception instanceof RequestException && in_array($exception->response->status(), [429, 500, 502, 503, 504])),
                        throw: false
                    )
                    ->post($this->endpoint($provider), $this->payload($prompt, $options, $model));

                $content = $this->extractContent($response);
                $this->log($feature, $provider, $model, $started, $response);

                return $content;
            } catch (ConnectionException $e) {
                $last = new AiException('Tidak bisa terhubung ke AI API: '.$e->getMessage(), 0, $e);
            } catch (AiException $e) {
                $last = $e;
            }

            $this->log($feature, $provider, $model, $started, $response ?? null, $last->getMessage());
            unset($response);
        }

        throw $last;
    }

    /**
     * Kirim banyak prompt sekaligus secara paralel (dipakai preview composer, supaya
     * 10 lead tidak butuh 10x waktu satu panggilan AI). Prompt yang gagal di provider
     * utama diulang sekali lewat provider cadangan.
     *
     * @param  array<string|int, string>  $prompts
     * @return array<string|int, string|AiException> teks jawaban, atau exception per prompt yang gagal
     */
    public function generateMany(array $prompts, array $options = [], int $concurrency = 5): array
    {
        if (! $this->isConfigured()) {
            return array_map(fn () => $this->notConfiguredException(), $prompts);
        }

        $results = [];
        $pending = $prompts;

        foreach ($this->providers as $provider) {
            if ($pending === []) {
                break;
            }

            foreach ($this->pool($provider, $pending, $options, $concurrency) as $key => $result) {
                $results[$key] = $result;

                if (is_string($result)) {
                    unset($pending[$key]);
                }
            }
        }

        // Urutan hasil mengikuti urutan prompt
        return array_replace(array_fill_keys(array_keys($prompts), null), $results);
    }

    /**
     * @return array<string|int, string|AiException>
     */
    protected function pool(array $provider, array $prompts, array $options, int $concurrency): array
    {
        $feature = $options['feature'] ?? 'other';
        $model = $this->modelFor($provider, $feature);
        $started = hrtime(true);

        $responses = Http::pool(function (Pool $pool) use ($prompts, $options, $provider, $model) {
            $requests = [];
            foreach ($prompts as $key => $prompt) {
                $requests[] = $pool->as((string) $key)
                    ->withToken($provider['key'])
                    ->acceptJson()
                    ->timeout($this->timeout)
                    ->post($this->endpoint($provider), $this->payload($prompt, $options, $model));
            }

            return $requests;
        }, $concurrency);

        $results = [];
        foreach (array_keys($prompts) as $key) {
            $response = $responses[(string) $key] ?? null;

            try {
                if (! $response instanceof Response) {
                    throw new AiException('Tidak bisa terhubung ke AI API'.($response instanceof \Throwable ? ': '.$response->getMessage() : '.'));
                }

                $results[$key] = $this->extractContent($response);
                $this->log($feature, $provider, $model, $started, $response);
            } catch (AiException $e) {
                $results[$key] = $e;
                $this->log($feature, $provider, $model, $started, $response instanceof Response ? $response : null, $e->getMessage());
            }
        }

        return $results;
    }

    protected function modelFor(array $provider, string $feature): string
    {
        return $provider['name'] === 'primary' && $this->fastModel && in_array($feature, self::FAST_FEATURES, true)
            ? $this->fastModel
            : $provider['model'];
    }

    protected function log(string $feature, array $provider, string $model, int $started, ?Response $response, ?string $error = null): void
    {
        $usage = $response?->json('usage');

        AiUsageLog::record([
            'feature' => array_key_exists($feature, AiUsageLog::FEATURES) ? $feature : 'other',
            'provider' => $provider['name'],
            'model' => mb_substr($model, 0, 150),
            'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            'prompt_tokens' => is_array($usage) ? ($usage['prompt_tokens'] ?? null) : null,
            'completion_tokens' => is_array($usage) ? ($usage['completion_tokens'] ?? null) : null,
            'total_tokens' => is_array($usage) ? ($usage['total_tokens'] ?? null) : null,
            'success' => $error === null,
            'error' => $error ? mb_substr($error, 0, 500) : null,
        ]);
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw $this->notConfiguredException();
        }
    }

    protected function notConfiguredException(): AiException
    {
        return new AiException('AI belum dikonfigurasi. Isi Base URL, API key (AI_API_KEY), dan model di Pengaturan → Koneksi.');
    }

    protected function endpoint(array $provider): string
    {
        return $provider['base_url'].'/chat/completions';
    }

    protected function request(array $provider): PendingRequest
    {
        return Http::withToken($provider['key'])->acceptJson()->timeout($this->timeout);
    }

    protected function payload(string $prompt, array $options, string $model): array
    {
        return [
            'model' => $model,
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
     * Hasil yang tidak lolos quality gate ditulis ulang sekali; jika masih bermasalah, ditandai perlu review.
     *
     * @param  array  $context  offer, sender, website, tone (formal|casual|friendly), language (id|en), instruction, variant
     * @return array{subject: ?string, message: string, needs_review: bool, problems: array<int, string>, variant: string}
     */
    public function generateOutreach(Lead $lead, string $channel, array $context = []): array
    {
        $context['variant'] = $this->variantFor($lead, $context);
        $prompt = $this->outreachPrompt($lead, $channel, $context);
        $options = ['temperature' => 0.8, 'feature' => 'outreach'];
        $gate = app(MessageQualityGate::class);

        $result = $this->parseOutreach($this->generateText($prompt, $options), $lead, $channel, $context);
        $problems = $gate->problems($result['message'], $result['subject'], $lead, $channel);

        if ($problems) {
            try {
                $retry = $this->parseOutreach($this->generateText(
                    $prompt."\n\nTulisan sebelumnya ditolak karena: ".implode('; ', $problems).'. Tulis ulang dari awal dan hindari masalah tersebut.',
                    $options
                ), $lead, $channel, $context);
                $retryProblems = $gate->problems($retry['message'], $retry['subject'], $lead, $channel);

                if (count($retryProblems) <= count($problems)) {
                    [$result, $problems] = [$retry, $retryProblems];
                }
            } catch (AiException $e) {
                report($e); // hasil pertama tetap dipakai, ditandai perlu review
            }
        }

        return $result + ['needs_review' => $problems !== [], 'problems' => $problems, 'variant' => $context['variant']];
    }

    /**
     * Varian gaya pembuka pesan, diukur reply rate-nya di dashboard.
     */
    public const PROMPT_VARIANTS = [
        'observasi' => 'Buka dengan pengamatan',
        'pertanyaan' => 'Buka dengan pertanyaan',
    ];

    /**
     * Varian untuk lead ini: dari konteks, atau dibagi rata berdasarkan ID lead (A/B yang stabil).
     */
    public function variantFor(Lead $lead, array $context = []): string
    {
        if (isset($context['variant']) && array_key_exists($context['variant'], self::PROMPT_VARIANTS)) {
            return $context['variant'];
        }

        if (Setting::get('ai_prompt_variants') !== '1') {
            return 'observasi';
        }

        $variants = array_keys(self::PROMPT_VARIANTS);

        return $variants[(int) $lead->id % count($variants)];
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
            ."- Buka dengan sapaan singkat ({$ctx['greeting']}), ".($this->variantFor($lead, $context) === 'pertanyaan'
                ? 'lalu satu pertanyaan singkat tentang cara calon pelanggan menemukan atau menghubungi mereka, lalu langsung ke alasan menghubungi.'
                : 'lalu satu pengamatan spesifik tentang bisnis mereka (dari yang diketahui di atas) dan langsung ke alasan menghubungi.')."\n"
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

        $raw = preg_replace('/^\s*(berikut( ini)?( adalah| merupakan)?|here is|here\'s)[^\n]*:\s*\n+/iu', '', $this->generateText($prompt, ['temperature' => 0.8, 'feature' => 'template']));

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

        // Hasil audit website (PageSpeed) memberi fakta nyata untuk dibahas.
        $findings = [];
        if ($lead->website_score !== null && $lead->website_score < 50) {
            $findings[] = 'website mereka terbuka lambat di HP menurut pengecekan Google PageSpeed';
        }
        if ($lead->website_https === false) {
            $findings[] = 'website mereka belum memakai HTTPS sehingga browser bisa menampilkan peringatan "Tidak aman"';
        }

        if ($findings) {
            return 'Mereka sudah punya website, dan dari pengecekan singkat: '.implode('; ', $findings).'. '
                .'Sebutkan temuan ini dengan sopan sebagai hasil pengecekan cepat (tanpa angka skor), lalu tawarkan perbaikannya. Jangan terdengar menggurui.';
        }

        return 'Mereka sudah punya website. Jangan menyiratkan websitenya buruk dan jangan mengaku sudah menilainya. '
            .'Tawarkan bantuan yang spesifik, misalnya tampilan mobile yang lebih cepat, halaman layanan yang lebih jelas, atau booking online, sebagai tawaran, bukan kritik.';
    }

    protected function socialPlatform(?string $url): ?string
    {
        return Url::socialPlatform($url);
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
        return $this->cleanMessage($this->generateText($this->polishDraftPrompt($lead, $draft, $context), ['feature' => 'polish']));
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

        return $this->cleanMessage($this->generateText($prompt, ['feature' => 'polish']));
    }

    /**
     * Pesan follow-up singkat untuk lead yang belum membalas.
     */
    /**
     * Follow-up / langkah sequence berikutnya. Kanal boleh berbeda dari pesan sebelumnya
     * (mis. email lalu WhatsApp); $final = pesan penutup sequence.
     */
    public function generateFollowup(Lead $lead, OutreachMessage $original, array $context = [], ?string $channel = null, bool $final = false): string
    {
        $ctx = $this->context($context);
        $channel ??= $original->type;
        $days = $original->sent_at ? max(1, (int) $original->sent_at->diffInDays(now())) : null;
        $label = fn (string $type) => $type === 'whatsapp' ? 'pesan WhatsApp' : 'email';
        $previous = $label($original->type).' sebelumnya'.($channel !== $original->type ? ' (dikirim lewat '.$label($original->type).')' : '');

        $prompt = "Tulis {$label($channel)} follow-up yang sangat singkat (2–3 kalimat, di bawah 50 kata) dalam {$ctx['language_label']} untuk {$lead->displayName()}"
            .($days ? ", karena {$previous} {$days} hari lalu belum dibalas" : ", karena {$previous} belum dibalas").".\n\n"
            ."Pesan sebelumnya:\n\"\"\"\n{$this->stripSignature($original->message)}\n\"\"\"\n\n"
            ."Aturan: sapaan singkat ({$ctx['greeting']}), sopan, tidak memaksa, tidak menyalahkan, tidak mengulang isi pesan sebelumnya. "
            .($final
                ? 'Ini pesan terakhir: sampaikan bahwa Anda tidak akan mengganggu lagi, dan pintu tetap terbuka jika suatu saat mereka butuh bantuan soal website. Tanpa pertanyaan yang mendesak. '
                : 'Ingatkan penawaran dalam satu kalimat, lalu satu pertanyaan ya/tidak yang mudah dijawab. ')
            .($channel !== $original->type ? 'Sebutkan singkat bahwa Anda sempat mengirim '.$label($original->type).'. ' : '')
            .'Jangan menulis salam penutup atau tanda tangan (ditambahkan otomatis). Tanpa markdown, tanpa subjek, tanpa kalimat pengantar, tanpa tanda seru.';

        $body = $this->stripSignature($this->cleanMessage($this->generateText($prompt, ['temperature' => 0.8, 'feature' => 'followup'])), $context);

        return $body."\n\n".$this->signature($channel, $context);
    }

    /**
     * Klasifikasi balasan calon klien. Mengembalikan salah satu kunci OutreachMessage::REPLY_CATEGORIES.
     *
     * @throws AiException
     */
    public function classifyReply(string $text, ?string $subject = null): string
    {
        $categories = array_keys(OutreachMessage::REPLY_CATEGORIES);

        $prompt = "Klasifikasikan balasan calon klien atas pesan penawaran jasa pembuatan website.\n"
            ."Kategori:\n"
            ."- interested: tertarik, ingin tahu lebih lanjut, minta contoh/portofolio, minta dihubungi atau diajak diskusi\n"
            ."- pricing: menanyakan harga, biaya, paket, atau budget\n"
            ."- not_interested: menolak, tidak butuh, sudah punya/sudah ada vendor\n"
            ."- auto_reply: balasan otomatis, out of office, menu bot, sapaan otomatis WhatsApp Business\n"
            ."- other: selain di atas\n\n"
            .($subject ? "Subjek: {$subject}\n" : '')
            ."Balasan:\n\"\"\"\n".mb_substr($text, 0, 1500)."\n\"\"\"\n\n"
            .'Jawab HANYA dengan satu kata kategori: '.implode(', ', $categories).'.';

        $answer = strtolower($this->generateText($prompt, [
            'feature' => 'classify',
            'temperature' => 0,
            'max_tokens' => 200,
            'system' => 'Anda mengklasifikasikan balasan pesan bisnis dengan tepat dan hanya menjawab dengan nama kategori.',
        ]));

        // Cocokkan kategori terpanjang dulu ("not_interested" mengandung "interested").
        usort($categories, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($categories as $category) {
            if (str_contains($answer, $category)) {
                return $category;
            }
        }

        throw new AiException('Jawaban klasifikasi AI tidak dikenali: '.mb_strimwidth($answer, 0, 80, '…'));
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

        $response = $this->generateText($prompt, ['temperature' => 0.1, 'feature' => 'match']);

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
        } elseif ($lead->website_score !== null && $lead->website_score < 50) {
            $lines[] = '- Website: sudah punya, tapi terbuka lambat di HP';
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
