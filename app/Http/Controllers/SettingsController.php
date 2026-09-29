<?php

namespace App\Http\Controllers;

use App\Exceptions\AiException;
use App\Helpers\Phone;
use App\Mail\OutreachMail;
use App\Services\WhatsApp\WhatsAppManager;
use App\Models\AiUsageLog;
use App\Models\BlacklistEntry;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\OutreachSender;
use App\Services\Replies\ImapMailbox;
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
            'ai_key_saved' => (bool) config('services.ai.key'),
            'ai_fast_model' => $settings['ai_fast_model'] ?: (config('services.ai.fast_model') ?: ''),
            'ai_backup_base_url' => $settings['ai_backup_base_url'] ?: (config('services.ai.backup.base_url') ?: ''),
            'ai_backup_model' => $settings['ai_backup_model'] ?: (config('services.ai.backup.model') ?: ''),
            'ai_backup_key_saved' => (bool) config('services.ai.backup.key'),
            'pagespeed_key_saved' => (bool) config('services.pagespeed.key'),
            'mail_mailer' => $settings['mail_mailer'] ?: (config('mail.default') === 'smtp' ? 'smtp' : 'log'),
            'mail_host' => $settings['mail_host'] ?: (config('mail.mailers.smtp.host') !== '127.0.0.1' ? config('mail.mailers.smtp.host') : 'smtp.gmail.com'),
            'mail_port' => $settings['mail_port'] ?: (config('mail.mailers.smtp.port') != 2525 ? config('mail.mailers.smtp.port') : 587),
            'mail_username' => $settings['mail_username'] ?: (config('mail.mailers.smtp.username') ?: ''),
            'mail_password_saved' => (bool) config('mail.mailers.smtp.password'),
            'mail_from_address' => $settings['mail_from_address'] ?: (config('mail.from.address') !== 'hello@example.com' ? config('mail.from.address') : ''),
            'mail_from_name' => $settings['mail_from_name'] ?: (config('mail.from.name') !== 'Laravel' ? config('mail.from.name') : ''),
            'imap_enabled' => (bool) config('leadhunter.imap.enabled'),
        ];

        $wa = config('leadhunter.whatsapp');
        $sending = [
            'wa_driver' => $wa['driver'] ?: 'manual',
            'wa_token_saved' => ! empty($wa['token']),
            'wa_base_url' => $wa['base_url'],
            'wa_sender' => $settings['wa_sender'],
            'wa_hourly_limit' => $wa['hourly_limit'],
            'wa_daily_limit' => $wa['daily_limit'],
            'wa_allow_landline' => (bool) $wa['allow_landline'],
            'wa_webhook_url' => $wa['webhook_token'] ? route('webhooks.whatsapp', $wa['webhook_token']) : null,
            'email_hourly_limit' => config('leadhunter.sending.hourly_limit'),
            'email_daily_limit' => config('leadhunter.sending.daily_limit'),
            'send_window_start' => config('leadhunter.sending.window_start'),
            'send_window_end' => config('leadhunter.sending.window_end'),
            'send_weekdays_only' => (bool) config('leadhunter.sending.weekdays_only'),
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

        $aiUsage = ['daily' => AiUsageLog::dailySummary(7), 'features' => AiUsageLog::featureSummary(7)];

        return view('settings.edit', compact('settings', 'blacklist', 'system', 'connections', 'sending', 'aiUsage'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sender_name' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:100',
            'company_website' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_tagline' => 'nullable|string|max:150',
            'company_logo_url' => 'nullable|url:http,https|max:255',
            'default_offer' => 'required|string|max:255',
            'followup_days' => 'required|integer|min:1|max:60',
        ]);

        $data['followup_enabled'] = $request->boolean('followup_enabled') ? '1' : '0';
        $data['ai_prompt_variants'] = $request->boolean('ai_prompt_variants') ? '1' : '0';
        $data['weekly_report_enabled'] = $request->boolean('weekly_report_enabled') ? '1' : '0';

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
            'ai_fast_model' => 'nullable|string|max:150',
            'ai_backup_base_url' => 'nullable|url:http,https|max:255',
            'ai_backup_api_key' => 'nullable|string|max:500',
            'ai_backup_model' => 'nullable|required_with:ai_backup_base_url|string|max:150',
            'pagespeed_api_key' => 'nullable|string|max:200',
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
            'ai_backup_model.required_with' => 'Model provider cadangan wajib diisi jika Base URL cadangan diisi.',
        ]);

        // App Password Gmail ditampilkan dengan spasi ("abcd efgh ijkl mnop"); spasinya bukan bagian password.
        if (! empty($data['mail_password'])) {
            $data['mail_password'] = str_replace(' ', '', $data['mail_password']);
        }

        foreach (['ai_api_key', 'ai_backup_api_key', 'mail_password', 'pagespeed_api_key'] as $secret) {
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

    /**
     * WhatsApp gateway + aturan pengiriman (batas, jam kirim) untuk semua channel.
     */
    public function updateSending(Request $request)
    {
        $data = $request->validate([
            'wa_driver' => 'required|in:'.implode(',', array_keys(WhatsAppManager::DRIVERS)),
            'wa_token' => 'nullable|string|max:500',
            'wa_base_url' => 'nullable|required_if:wa_driver,wablas|url:https,http|max:255',
            'wa_sender' => 'nullable|string|max:30',
            'wa_hourly_limit' => 'required|integer|min:1|max:100',
            'wa_daily_limit' => 'required|integer|min:1|max:500',
            'email_hourly_limit' => 'required|integer|min:1|max:200',
            'email_daily_limit' => 'required|integer|min:1|max:2000',
            'send_window_start' => 'required|date_format:H:i',
            'send_window_end' => 'required|date_format:H:i|after:send_window_start',
        ], [
            'wa_base_url.required_if' => 'URL server Wablas wajib diisi (mis. https://tegal.wablas.com).',
            'send_window_end.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        if ($request->boolean('clear_wa_token')) {
            $data['wa_token'] = '';
        } elseif (($data['wa_token'] ?? '') === '') {
            unset($data['wa_token']);
        }

        if ($data['wa_driver'] !== 'manual' && ! Setting::get('wa_token') && empty($data['wa_token'])) {
            return back()->withErrors(['wa_token' => 'Token gateway wajib diisi untuk '.WhatsAppManager::DRIVERS[$data['wa_driver']].'.'])->withInput();
        }

        $data['wa_allow_landline'] = $request->boolean('wa_allow_landline') ? '1' : '0';
        $data['send_weekdays_only'] = $request->boolean('send_weekdays_only') ? '1' : '0';

        // Token rahasia untuk URL webhook dibuat sekali.
        if (! Setting::get('wa_webhook_token')) {
            $data['wa_webhook_token'] = Str::random(40);
        }

        Setting::put(array_map(fn ($v) => $v ?? '', $data));

        return redirect()->to(route('settings.edit').'#pengiriman')->with('success', 'Pengaturan WhatsApp & pengiriman disimpan.');
    }

    public function testWhatsApp(Request $request, WhatsAppManager $whatsapp)
    {
        $request->validate(['number' => 'required|string|max:30'], ['number.required' => 'Isi nomor WhatsApp tujuan tes.']);

        $number = Phone::toWhatsApp($request->number);
        if (! $number) {
            return redirect()->to(route('settings.edit').'#pengiriman')->with('error', 'Nomor tidak valid.');
        }

        $gateway = $whatsapp->driver();
        if (! $gateway->isAutomatic()) {
            return redirect()->to(route('settings.edit').'#pengiriman')->with('error', 'Pilih Fonnte atau Wablas dan simpan pengaturan dulu.');
        }

        $result = $gateway->send($number, 'Tes WhatsApp dari LeadHunter AI. Jika pesan ini sampai, gateway siap dipakai.');

        if (! $result->ok) {
            return redirect()->to(route('settings.edit').'#pengiriman')->with('error', 'Tes WhatsApp gagal: '.$result->error);
        }

        Setting::put(['wa_tested_at' => now()->toDateTimeString()]);

        return redirect()->to(route('settings.edit').'#pengiriman')->with('success', "WhatsApp tes terkirim ke {$number}.");
    }

    public function testAi(AiService $ai)
    {
        set_time_limit(300);
        $started = microtime(true);

        try {
            $reply = $ai->generateText('Balas hanya dengan satu kata: OK', ['max_tokens' => 10, 'temperature' => 0, 'feature' => 'test']);
        } catch (AiException $e) {
            return redirect()->to(route('settings.edit').'#koneksi')->with('error', 'Tes AI gagal: '.$e->getMessage());
        }

        $seconds = round(microtime(true) - $started, 1);
        Setting::put(['ai_tested_at' => now()->toDateTimeString()]);

        return redirect()->to(route('settings.edit').'#koneksi')
            ->with('success', 'AI terhubung (model '.config('services.ai.model').", {$seconds} detik). Jawaban: \"".Str::limit($reply, 60).'"');
    }

    /**
     * Cek login IMAP (deteksi balasan) dengan membaca header email 3 hari terakhir.
     */
    public function testImap(ImapMailbox $mailbox)
    {
        set_time_limit(120);

        try {
            $count = count($mailbox->fetchSince(now()->subDays(3)));
        } catch (Throwable $e) {
            return redirect()->to(route('settings.edit').'#koneksi')->with('error', 'Tes IMAP gagal: '.Str::limit($e->getMessage(), 300));
        }

        return redirect()->to(route('settings.edit').'#koneksi')
            ->with('success', "IMAP terhubung: {$count} email masuk dalam 3 hari terakhir terbaca.".(config('leadhunter.imap.enabled') ? '' : ' Centang "Deteksi balasan otomatis" lalu simpan untuk mengaktifkannya.'));
    }

    /**
     * Contoh tampilan email outreach dengan identitas usaha saat ini (tanpa mengirim apa pun).
     */
    public function emailPreview()
    {
        return response($this->sampleOutreachMail('Website untuk Klinik Gigi Senyum')->render());
    }

    /**
     * Email tes memakai template yang sama dengan email ke client, jadi tampilannya bisa dicek di inbox sungguhan.
     */
    public function testMail(Request $request, OutreachSender $sender)
    {
        $to = config('mail.from.address') && config('mail.from.address') !== 'hello@example.com'
            ? config('mail.from.address')
            : $request->user()->email;

        try {
            Mail::to($to)->send($this->sampleOutreachMail('Tes email LeadHunter AI: contoh tampilan outreach', $sender->unsubscribeMailto()));
        } catch (Throwable $e) {
            return redirect()->to(route('settings.edit').'#koneksi')->with('error', 'Tes email gagal: '.Str::limit($e->getMessage(), 300));
        }

        $message = $sender->isFakeMailer()
            ? "Mode kirim masih \"log\": email tes hanya ditulis ke storage/logs/laravel.log. Pilih mode SMTP untuk mengirim sungguhan."
            : "Email tes terkirim ke {$to} dengan tampilan yang sama seperti email ke client. Cek inbox (atau folder spam).";

        if (! $sender->isFakeMailer()) {
            Setting::put(['mail_tested_at' => now()->toDateTimeString()]);
        }

        return redirect()->to(route('settings.edit').'#koneksi')->with($sender->isFakeMailer() ? 'error' : 'success', $message);
    }

    /**
     * Contoh email outreach dengan identitas usaha saat ini, dipakai pratinjau dan email tes.
     */
    protected function sampleOutreachMail(string $subject, ?string $unsubscribeMailto = null): OutreachMail
    {
        $offer = Setting::defaultOffer();
        $message = "Halo tim Klinik Gigi Senyum,\n\n"
            ."Saya lihat Klinik Gigi Senyum punya rating 4,8 dari 320 ulasan di Google Maps. Sayangnya saya belum menemukan website resminya, padahal banyak pasien baru mencari klinik lewat Google.\n\n"
            ."Kami membantu usaha seperti Anda lewat {$offer}. Kalau berkenan, saya kirimkan contoh desain untuk klinik Anda, gratis.\n\n"
            ."Salam,\n".Setting::senderIdentity();

        return new OutreachMail($subject, $message, unsubscribeMailto: $unsubscribeMailto);
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
