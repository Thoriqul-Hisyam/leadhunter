<?php

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Models\BlacklistEntry;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\OutreachSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SettingsController extends Controller
{
    public function edit(AiService $ai, OutreachSender $sender)
    {
        $settings = Setting::values();
        $blacklist = BlacklistEntry::latest()->paginate(20);

        // Nilai efektif (database, atau .env jika belum diisi). Secret tidak pernah dikirim ke view.
        $connections = [
            'ai_base_url' => $settings['ai_base_url'] ?: config('services.ai.base_url'),
            'ai_model' => $settings['ai_model'] ?: config('services.ai.model'),
            'ai_key_saved' => $ai->isConfigured(),
            'mail_mailer' => $settings['mail_mailer'] ?: (config('mail.default') === 'smtp' ? 'smtp' : 'log'),
            'mail_host' => $settings['mail_host'] ?: (config('mail.mailers.smtp.host') !== '127.0.0.1' ? config('mail.mailers.smtp.host') : 'smtp.gmail.com'),
            'mail_port' => $settings['mail_port'] ?: (config('mail.mailers.smtp.port') != 2525 ? config('mail.mailers.smtp.port') : 587),
            'mail_username' => $settings['mail_username'] ?: (config('mail.mailers.smtp.username') ?: ''),
            'mail_password_saved' => (bool) config('mail.mailers.smtp.password'),
            'mail_from_address' => $settings['mail_from_address'] ?: (config('mail.from.address') !== 'hello@example.com' ? config('mail.from.address') : ''),
            'mail_from_name' => $settings['mail_from_name'] ?: (config('mail.from.name') !== 'Laravel' ? config('mail.from.name') : ''),
            'imap_enabled' => (bool) config('leadhunter.imap.enabled'),
        ];

        $system = [
            'ai_configured' => $ai->isConfigured(),
            'ai_model' => config('services.ai.model'),
            'ai_base_url' => config('services.ai.base_url'),
            'mailer' => config('mail.default'),
            'fake_mailer' => $sender->isFakeMailer(),
            'mail_from' => config('mail.from.address'),
            'queue' => config('queue.default'),
            'scraper_driver' => config('leadhunter.scraper.driver'),
            'hourly_limit' => config('leadhunter.sending.hourly_limit'),
            'imap_enabled' => config('leadhunter.imap.enabled'),
        ];

        return view('settings.edit', compact('settings', 'blacklist', 'system', 'connections'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sender_name' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:100',
            'company_website' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_tagline' => 'nullable|string|max:150',
            'default_offer' => 'required|string|max:255',
            'followup_days' => 'required|integer|min:1|max:60',
        ]);

        $data['followup_enabled'] = $request->boolean('followup_enabled') ? '1' : '0';

        Setting::put($data);

        return redirect()->route('settings.edit')->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Koneksi AI & email disimpan di database (secret terenkripsi), bukan di .env.
     * Field secret yang dikosongkan berarti "tidak diubah".
     */
    public function updateConnections(Request $request)
    {
        $data = $request->validate([
            'ai_base_url' => 'nullable|url:http,https|max:255',
            'ai_api_key' => 'nullable|string|max:500',
            'ai_model' => 'nullable|string|max:150',
            'mail_mailer' => 'required|in:smtp,log',
            'mail_host' => 'nullable|required_if:mail_mailer,smtp|string|max:255',
            'mail_port' => 'nullable|required_if:mail_mailer,smtp|integer|min:1|max:65535',
            'mail_username' => 'nullable|required_if:mail_mailer,smtp|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|required_if:mail_mailer,smtp|email|max:255',
            'mail_from_name' => 'nullable|string|max:150',
        ], [
            'mail_host.required_if' => 'Host SMTP wajib diisi untuk mode SMTP.',
            'mail_port.required_if' => 'Port SMTP wajib diisi untuk mode SMTP.',
            'mail_username.required_if' => 'Username (alamat Gmail) wajib diisi untuk mode SMTP.',
            'mail_from_address.required_if' => 'Alamat pengirim wajib diisi untuk mode SMTP.',
        ]);

        // App Password Gmail ditampilkan dengan spasi ("abcd efgh ijkl mnop"); spasinya bukan bagian password.
        if (! empty($data['mail_password'])) {
            $data['mail_password'] = str_replace(' ', '', $data['mail_password']);
        }

        foreach (['ai_api_key', 'mail_password'] as $secret) {
            if ($request->boolean("clear_{$secret}")) {
                $data[$secret] = '';
            } elseif (($data[$secret] ?? '') === '') {
                unset($data[$secret]); // kosong = pertahankan nilai tersimpan
            }
        }

        $data['imap_enabled'] = $request->boolean('imap_enabled') ? '1' : '0';
        $data = array_map(fn ($v) => $v ?? '', $data);

        Setting::put($data);

        return redirect()->to(route('settings.edit').'#koneksi')->with('success', 'Koneksi AI & email berhasil disimpan.');
    }

    public function testAi(AiService $ai)
    {
        set_time_limit(300);
        $started = microtime(true);

        try {
            $reply = $ai->generateText('Balas hanya dengan satu kata: OK', ['max_tokens' => 10, 'temperature' => 0]);
        } catch (AiException $e) {
            return redirect()->to(route('settings.edit').'#koneksi')->with('error', 'Tes AI gagal: '.$e->getMessage());
        }

        $seconds = round(microtime(true) - $started, 1);

        return redirect()->to(route('settings.edit').'#koneksi')
            ->with('success', 'AI terhubung (model '.config('services.ai.model').", {$seconds} detik). Jawaban: \"".Str::limit($reply, 60).'"');
    }

    public function testMail(Request $request, OutreachSender $sender)
    {
        $to = config('mail.from.address') && config('mail.from.address') !== 'hello@example.com'
            ? config('mail.from.address')
            : $request->user()->email;

        try {
            Mail::raw(
                "Ini email tes dari LeadHunter AI.\n\nJika email ini sampai di inbox, pengaturan SMTP sudah benar dan outreach email siap dikirim.",
                fn ($message) => $message->to($to)->subject('Tes email LeadHunter AI')
            );
        } catch (Throwable $e) {
            return redirect()->to(route('settings.edit').'#koneksi')->with('error', 'Tes email gagal: '.Str::limit($e->getMessage(), 300));
        }

        $message = $sender->isFakeMailer()
            ? "Mode kirim masih \"log\": email tes hanya ditulis ke storage/logs/laravel.log. Pilih mode SMTP untuk mengirim sungguhan."
            : "Email tes terkirim ke {$to}. Cek inbox (atau folder spam).";

        return redirect()->to(route('settings.edit').'#koneksi')->with($sender->isFakeMailer() ? 'error' : 'success', $message);
    }

    public function storeBlacklist(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:email,phone',
            'value' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($data['type'] === 'email' && ! filter_var($data['value'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withErrors(['value' => 'Format email tidak valid.'])->withInput();
        }

        BlacklistEntry::add($data['type'], $data['value'], ($data['reason'] ?? null) ?: 'manual');

        return redirect()->to(route('settings.edit').'#blacklist')->with('success', 'Ditambahkan ke blacklist. Pesan ke kontak ini tidak akan dikirim.');
    }

    public function destroyBlacklist(BlacklistEntry $entry)
    {
        $entry->delete();

        return redirect()->to(route('settings.edit').'#blacklist')->with('success', 'Dihapus dari blacklist.');
    }
}
