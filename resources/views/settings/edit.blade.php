@extends('layouts.app')

@section('title', 'Pengaturan — Sandesa')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Pengaturan</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Identitas pengirim dipakai di prompt AI, placeholder template, dan footer email.</p>
    </div>

    @if(session('success'))
        <div class="alert-success"><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="alert-error"><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="alert-error"><span>{{ $errors->first() }}</span></div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form pengaturan --}}
        <form action="{{ route('settings.update') }}" method="POST" class="glass-card p-6 space-y-5 lg:col-span-2">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Identitas Pengirim</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sender_name" class="form-label text-xs">Nama pengirim</label>
                        <input type="text" id="sender_name" name="sender_name" value="{{ old('sender_name', $settings['sender_name']) }}" required class="form-input text-xs" placeholder="Thoriq">
                    </div>
                    <div>
                        <label for="company_name" class="form-label text-xs">Nama usaha</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" class="form-input text-xs" placeholder="Lefateach">
                    </div>
                    <div>
                        <label for="company_website" class="form-label text-xs">Website usaha</label>
                        <input type="text" id="company_website" name="company_website" value="{{ old('company_website', $settings['company_website']) }}" class="form-input text-xs" placeholder="lefateach.com">
                    </div>
                    <div>
                        <label for="company_phone" class="form-label text-xs">Telepon / WA usaha</label>
                        <input type="text" id="company_phone" name="company_phone" value="{{ old('company_phone', $settings['company_phone']) }}" class="form-input text-xs" placeholder="0895-...">
                    </div>
                    <div class="md:col-span-2">
                        <label for="company_tagline" class="form-label text-xs">Tagline (header email)</label>
                        <input type="text" id="company_tagline" name="company_tagline" value="{{ old('company_tagline', $settings['company_tagline']) }}" class="form-input text-xs" placeholder="#1 Jasa Website di Indonesia">
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 mt-2">Tampil sebagai: <strong>{{ \App\Models\Setting::senderIdentity() }}</strong> (placeholder <code>@{{sender_name}}</code>).</p>
            </div>

            <div class="border-t border-slate-200/60 dark:border-slate-800/60 pt-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Penawaran Default</h3>
                <input type="text" name="default_offer" value="{{ old('default_offer', $settings['default_offer']) }}" required class="form-input text-xs" placeholder="Jasa Pembuatan Website Profesional">
                <p class="text-[10px] text-slate-500 mt-1">Dipakai AI dan placeholder <code>@{{offer}}</code> jika penawaran tidak diisi di composer.</p>
            </div>

            <div class="border-t border-slate-200/60 dark:border-slate-800/60 pt-5">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Follow-up Otomatis</h3>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="checkbox" name="followup_enabled" value="1" class="form-checkbox rounded text-indigo-600" @checked(old('followup_enabled', $settings['followup_enabled']) == '1')>
                    Buat satu pesan follow-up untuk lead yang belum membalas
                </label>
                <div class="flex items-center gap-2 mt-3">
                    <span class="text-xs text-slate-600 dark:text-slate-300">setelah</span>
                    <input type="number" name="followup_days" min="1" max="60" value="{{ old('followup_days', $settings['followup_days']) }}" class="form-input text-xs w-20">
                    <span class="text-xs text-slate-600 dark:text-slate-300">hari.</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-2">Follow-up dibuat dengan status <strong>Pending</strong> (tidak langsung dikirim) supaya bisa direview dulu. Scheduler harus berjalan.</p>
            </div>

            <div class="flex justify-end border-t border-slate-200/60 dark:border-slate-800/60 pt-5">
                <button type="submit" class="btn-primary px-6 py-2.5 text-xs font-bold">Simpan Pengaturan</button>
            </div>
        </form>

        {{-- Status sistem --}}
        <div class="glass-card p-6 space-y-3 h-fit">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Status Sistem</h3>
            @php
                $rows = [
                    ['AI', $system['ai_configured'] ? $system['ai_model'] : 'Belum dikonfigurasi (AI_API_KEY kosong)', $system['ai_configured']],
                    ['Email', $system['fake_mailer'] ? "MAIL_MAILER={$system['mailer']} (hanya log)" : "{$system['mailer']} · {$system['mail_from']}", ! $system['fake_mailer']],
                    ['Queue', $system['queue'], $system['queue'] !== 'sync'],
                    ['Scraper', $system['scraper_driver'], true],
                    ['Batas kirim', "{$system['hourly_limit']} email/jam", true],
                    ['Deteksi reply (IMAP)', $system['imap_enabled'] ? 'Aktif' : 'Nonaktif (IMAP_ENABLED=false)', $system['imap_enabled']],
                ];
            @endphp
            <dl class="space-y-2.5">
                @foreach($rows as [$label, $value, $ok])
                    <div class="flex items-start gap-2">
                        <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ $ok ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                            <dd class="text-xs text-slate-700 dark:text-slate-200 break-all">{{ $value }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
            <p class="text-[10px] text-slate-500">Nilai ini diatur lewat file <code>.env</code>.</p>
        </div>
    </div>

    {{-- Koneksi AI & Email --}}
    <form action="{{ route('settings.connections') }}" method="POST" class="glass-card p-6 space-y-6" id="koneksi">
        @csrf
        @method('PUT')

        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Koneksi</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Disimpan di database; API key dan password dienkripsi. Kolom rahasia yang dikosongkan tidak mengubah nilai yang sudah tersimpan.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- AI --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <x-icon name="sparkles" class="w-4 h-4 text-indigo-500" />
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">AI (OpenAI-compatible)</h4>
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full {{ $connections['ai_key_saved'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">{{ $connections['ai_key_saved'] ? 'Terhubung' : 'Belum diisi' }}</span>
                </div>
                <div>
                    <label for="ai_base_url" class="form-label text-xs">Base URL</label>
                    <input type="url" id="ai_base_url" name="ai_base_url" value="{{ old('ai_base_url', $connections['ai_base_url']) }}" class="form-input text-xs" placeholder="https://api.groq.com/openai/v1">
                    <p class="text-[10px] text-slate-500 mt-1">Groq, 9router, OpenRouter, atau API lain yang kompatibel dengan OpenAI (endpoint <code>/chat/completions</code>).</p>
                </div>
                <div>
                    <label for="ai_api_key" class="form-label text-xs">API Key</label>
                    <input type="password" id="ai_api_key" name="ai_api_key" autocomplete="new-password" class="form-input text-xs" placeholder="{{ $connections['ai_key_saved'] ? '•••••••• (tersimpan, isi untuk mengganti)' : 'sk-...' }}">
                    @if($connections['ai_key_saved'])
                        <label class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-1.5 cursor-pointer">
                            <input type="checkbox" name="clear_ai_api_key" value="1" class="form-checkbox rounded"> Hapus API key tersimpan
                        </label>
                    @endif
                </div>
                <div>
                    <label for="ai_model" class="form-label text-xs">Model</label>
                    <input type="text" id="ai_model" name="ai_model" value="{{ old('ai_model', $connections['ai_model']) }}" class="form-input text-xs" placeholder="llama-3.1-8b-instant">
                </div>
            </div>

            {{-- Email --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <x-icon name="envelope" class="w-4 h-4 text-indigo-500" />
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Email (SMTP)</h4>
                    <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full {{ $connections['mail_mailer'] === 'smtp' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">{{ $connections['mail_mailer'] === 'smtp' ? 'SMTP aktif' : 'Mode log (tidak terkirim)' }}</span>
                </div>
                <div>
                    <span class="form-label text-xs">Mode kirim</span>
                    <div class="flex gap-2">
                        @foreach(['smtp' => 'SMTP (kirim sungguhan)', 'log' => 'Log saja (untuk uji coba)'] as $value => $label)
                            <label class="flex-1 flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 cursor-pointer transition has-checked:border-indigo-500/40 has-checked:bg-indigo-500/5 has-checked:text-indigo-700 dark:has-checked:text-indigo-300">
                                <input type="radio" name="mail_mailer" value="{{ $value }}" @checked(old('mail_mailer', $connections['mail_mailer']) === $value) class="form-radio"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label for="mail_host" class="form-label text-xs">Host SMTP</label>
                        <input type="text" id="mail_host" name="mail_host" value="{{ old('mail_host', $connections['mail_host']) }}" class="form-input text-xs" placeholder="smtp.gmail.com">
                    </div>
                    <div>
                        <label for="mail_port" class="form-label text-xs">Port</label>
                        <input type="number" id="mail_port" name="mail_port" value="{{ old('mail_port', $connections['mail_port']) }}" class="form-input text-xs" placeholder="587">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label for="mail_username" class="form-label text-xs">Username (alamat Gmail)</label>
                        <input type="text" id="mail_username" name="mail_username" value="{{ old('mail_username', $connections['mail_username']) }}" autocomplete="off" class="form-input text-xs" placeholder="emailanda@gmail.com">
                    </div>
                    <div>
                        <label for="mail_password" class="form-label text-xs">App Password</label>
                        <input type="password" id="mail_password" name="mail_password" autocomplete="new-password" class="form-input text-xs" placeholder="{{ $connections['mail_password_saved'] ? '•••••••• (tersimpan)' : '16 karakter' }}">
                        @if($connections['mail_password_saved'])
                            <label class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-1.5 cursor-pointer">
                                <input type="checkbox" name="clear_mail_password" value="1" class="form-checkbox rounded"> Hapus password tersimpan
                            </label>
                        @endif
                    </div>
                    <div>
                        <label for="mail_from_address" class="form-label text-xs">Alamat pengirim</label>
                        <input type="email" id="mail_from_address" name="mail_from_address" value="{{ old('mail_from_address', $connections['mail_from_address']) }}" class="form-input text-xs" placeholder="emailanda@gmail.com">
                    </div>
                    <div>
                        <label for="mail_from_name" class="form-label text-xs">Nama pengirim</label>
                        <input type="text" id="mail_from_name" name="mail_from_name" value="{{ old('mail_from_name', $connections['mail_from_name']) }}" class="form-input text-xs" placeholder="Thoriq · Lefateach">
                    </div>
                </div>
                <p class="text-[10px] text-slate-500">Gmail: aktifkan verifikasi 2 langkah, lalu buat <strong>App Password</strong> di myaccount.google.com/apppasswords. Password akun biasa tidak akan bisa dipakai.</p>
                <label class="flex items-start gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="checkbox" name="imap_enabled" value="1" class="form-checkbox rounded mt-0.5" @checked(old('imap_enabled', $connections['imap_enabled']))>
                    <span>Deteksi balasan otomatis lewat IMAP<br><span class="font-normal text-[10px] text-slate-500">Memakai akun Gmail yang sama. Aktifkan juga IMAP di Gmail (Setelan → Penerusan dan POP/IMAP).</span></span>
                </label>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-200/60 dark:border-slate-800/60 pt-5">
            <div class="flex flex-wrap gap-2">
                <button type="submit" form="test-ai-form" class="btn-secondary py-2 px-4 text-xs font-bold"><x-icon name="sparkles" class="w-3.5 h-3.5" /> Tes AI</button>
                <button type="submit" form="test-mail-form" class="btn-secondary py-2 px-4 text-xs font-bold"><x-icon name="send" class="w-3.5 h-3.5" /> Kirim email tes</button>
                <span class="text-[10px] text-slate-500 self-center">Simpan dulu sebelum mengetes.</span>
            </div>
            <button type="submit" class="btn-primary px-6 py-2.5 text-xs font-bold">Simpan Koneksi</button>
        </div>
    </form>
    <form id="test-ai-form" action="{{ route('settings.test-ai') }}" method="POST" class="hidden" onsubmit="const b = document.querySelector('[form=test-ai-form]'); b.disabled = true; b.lastChild.textContent = ' Menghubungi AI...';">@csrf</form>
    <form id="test-mail-form" action="{{ route('settings.test-mail') }}" method="POST" class="hidden">@csrf</form>

    {{-- Blacklist --}}
    <div class="glass-card p-6" id="blacklist">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Blacklist</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Email/nomor di sini tidak akan pernah dikirimi outreach. Penerima yang klik "Berhenti berlangganan" masuk otomatis.</p>
            </div>
            <form action="{{ route('blacklist.store') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <select name="type" class="form-input text-xs sm:w-28">
                    <option value="email">Email</option>
                    <option value="phone">Telepon</option>
                </select>
                <input type="text" name="value" required placeholder="email@contoh.com / 0812..." class="form-input text-xs sm:w-56">
                <input type="text" name="reason" placeholder="Alasan (opsional)" class="form-input text-xs sm:w-40">
                <button type="submit" class="btn-secondary py-1.5 px-4 text-xs font-bold">Tambah</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="fancy-table min-w-full">
                <thead>
                    <tr>
                        <th>Tipe</th>
                        <th>Nilai</th>
                        <th>Alasan</th>
                        <th>Ditambahkan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blacklist as $entry)
                        <tr>
                            <td class="text-xs">{{ $entry->type === 'phone' ? 'Telepon' : 'Email' }}</td>
                            <td class="text-xs font-semibold">{{ $entry->value }}</td>
                            <td class="text-xs">{{ $entry->reason }}</td>
                            <td class="text-xs text-slate-500">{{ $entry->created_at->format('d M Y H:i') }}</td>
                            <td class="text-right">
                                <form action="{{ route('blacklist.destroy', $entry) }}" method="POST" onsubmit="return handleConfirm(event, this, 'Hapus dari blacklist?', 'Kontak ini bisa dikirimi outreach lagi.', 'Ya, hapus')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-slate-500 hover:text-rose-500">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-xs text-slate-400 text-center py-6">Blacklist masih kosong.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $blacklist->fragment('blacklist')->links() }}</div>
    </div>
</div>
@endsection
