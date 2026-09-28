# LeadHunter AI (Sandesa)

Aplikasi untuk freelancer, agency, dan digital marketer yang mencari calon klien secara otomatis: scraping bisnis dari Google Maps, mencari email/WhatsApp dari website mereka, membuat pesan outreach personal dengan AI, lalu mengirim dan melacak hasilnya.

Dibuat dengan pendekatan MVP: sederhana, cepat, langsung bisa dipakai.

## Fitur

**Lead**
- Scraping bisnis dari Google Maps berdasarkan niche + kota (nama, alamat, telepon, website, rating, jumlah ulasan, kategori, link Maps); deteksi CAPTCHA dengan pesan gagal yang jelas
- Dedup berdasarkan place id Google Maps, jadi cabang dengan nama sama tetap terpisah
- Skor prioritas 0–100 dan label **Hot** (butuh website, populer, bisa dihubungi); sort & filter berdasarkan skor
- Deteksi nomor seluler vs telepon kantor (WhatsApp otomatis hanya ke nomor seluler)
- Aksi massal: cari email dari website, audit website (Google PageSpeed: kecepatan mobile & HTTPS), pindah stage; bisa untuk semua hasil filter
- Pipeline lead dengan pencarian: Baru → Dihubungi → Membalas → Meeting → Deal / Batal, plus catatan per lead
- Import dan export CSV

**Outreach**
- Pesan email & WhatsApp dengan AI, template, atau template yang dipoles AI (hybrid); temuan audit website dipakai sebagai sudut pesan
- Generate pesan untuk banyak lead sekaligus di background, dengan progress bar
- Message Template yang bisa ditulis AI, dengan placeholder seperti `{{business_name}}`
- Kirim lewat antrean dengan batas per jam & per hari, jeda acak, dan jam kirim (default Senin–Jumat 08.00–16.00 WIB)
- WhatsApp otomatis lewat gateway **Fonnte** atau **Wablas**, atau click-to-chat (`wa.me`) jika tanpa gateway
- **Sequence** per campaign: hingga 4 langkah lanjutan lintas kanal (mis. email → WhatsApp +3 hari → email penutup +4 hari), berhenti otomatis saat lead membalas atau minta berhenti
- Deteksi balasan: IMAP Gmail untuk email, webhook gateway untuk WhatsApp; bounce masuk blacklist, balasan otomatis (out of office, sapaan WhatsApp Business) tidak dihitung
- Klasifikasi balasan: tertarik / tanya harga / tidak tertarik / balasan otomatis, dengan notifikasi untuk lead yang tertarik
- Quality gate untuk pesan AI (placeholder, frasa klise, menyebut rating/alamat, panjang); pesan bermasalah ditulis ulang sekali lalu ditandai *Perlu review*
- Link unsubscribe dan blacklist

**AI**
- Provider apa saja yang kompatibel dengan OpenAI, plus **provider cadangan** otomatis saat provider utama gagal dan **model cepat** untuk tugas pendek
- Log pemakaian (jumlah panggilan, lama respons, token, error) dengan ringkasan 7 hari di Pengaturan
- Uji dua gaya pembuka pesan (pengamatan vs pertanyaan) dan bandingkan reply rate-nya

**Lainnya**
- Dashboard: sent rate, reply rate, grafik 30 hari, performa per campaign, reply rate per template, mode, varian prompt, langkah sequence, dan pola subjek email; checklist onboarding untuk pengguna baru
- Banner peringatan saat queue worker atau scheduler mati, halaman Antrean untuk job gagal (retry/hapus)
- Laporan mingguan via email ke admin setiap Senin pagi
- Role & permission (admin / user), lupa password, pencatatan pembuat data (`created_by`) untuk mode tim nanti
- Mode terang dan gelap, UI berbahasa Indonesia

## Tech stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Blade, Tailwind CSS v4, Vite, Chart.js |
| Database | MySQL (test memakai SQLite in-memory) |
| Queue & scheduler | Laravel queue (driver database) + scheduler |
| AI | API apa saja yang kompatibel dengan OpenAI (Groq, OpenRouter, 9router, dll) |
| Email | SMTP (Gmail App Password) |
| Scraping | Puppeteer + Google Chrome; alternatif Google Places API atau Apify |
| Test | Pest 4 |

## Kebutuhan

- PHP **8.3** atau lebih baru, dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `dom`, `intl` (untuk test juga `pdo_sqlite`)
- Composer 2
- Node.js **22.12** atau lebih baru
- MySQL 8
- Google Chrome (untuk scraper Puppeteer)

> **Laragon:** pilih PHP 8.3 lewat klik kanan Laragon → PHP → Version, lalu buka ulang terminal. Cek dengan `php -v`.

## Instalasi

```bash
git clone <repo-url> leadhunter
cd leadhunter

composer install
cp .env.example .env
php artisan key:generate

# buat database dulu, lalu:
mysql -u root -e "CREATE DATABASE IF NOT EXISTS leadhunter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed

npm install
npm run build
```

Atau sekaligus: `composer run setup`. Script ini hanya membuat `APP_KEY` jika belum ada.

> **Jangan mengganti `APP_KEY` setelah aplikasi dipakai.** API key AI dan password SMTP disimpan terenkripsi dengan key ini; kalau key berubah, keduanya harus diisi ulang di halaman Pengaturan.

## Menjalankan

```bash
composer run dev
```

Perintah ini menjalankan lima proses sekaligus:

| Proses | Fungsi |
|---|---|
| `php artisan serve` | web server di http://127.0.0.1:8000 |
| `queue:listen --queue=default` | generate pesan AI, kirim email & WhatsApp, audit website, klasifikasi balasan |
| `queue:listen --queue=scraping` | scraping Google Maps (bisa sampai 15 menit), cari email dari website |
| `schedule:work` | antrean kirim, retry, sequence follow-up, cek balasan, heartbeat, laporan mingguan, cleanup |
| `npm run dev` | Vite (hot reload CSS/JS) |

Login default dari seeder (ganti sebelum dipakai orang lain):

| Role | Email | Password |
|---|---|---|
| Admin | `admin@leadhunter.com` | `admin123` (atau nilai `ADMIN_PASSWORD` saat seeding) |
| User | `test@example.com` | `password` |

> **Tips Windows:** `php artisan serve` hanya melayani satu request pada satu waktu, jadi saat preview AI berjalan halaman lain akan menunggu. Untuk pemakaian harian lebih nyaman memakai virtual host Laragon (Apache), sementara `composer run dev` tetap dijalankan untuk queue dan scheduler.

## Konfigurasi

### Dari halaman Pengaturan (disimpan di database)

Login sebagai admin, buka menu profil → **Pengaturan & Blacklist**.

- **Identitas pengirim:** nama, nama usaha, website, telepon, tagline. Dipakai di prompt AI, placeholder template, dan header/footer email.
- **Penawaran default**, misalnya "Jasa Pembuatan Website Profesional".
- **Follow-up otomatis (default):** satu follow-up setelah N hari, untuk campaign yang belum punya sequence sendiri. Sequence multi-langkah diatur di halaman detail campaign.
- **Uji gaya pesan AI** dan **laporan mingguan:** aktif/nonaktif.
- **Koneksi AI:** Base URL, API key, model, model cepat (opsional), dan provider cadangan (opsional). Tekan **Tes AI** setelah menyimpan. Di bawahnya ada ringkasan pemakaian AI 7 hari.
- **Google PageSpeed API key** untuk audit website. Secara teknis opsional, tapi tanpa key kuota bersama Google sering sudah habis; buat key gratis di Google Cloud Console.
- **Email (SMTP):** mode kirim (SMTP atau log), host, port, username Gmail, App Password, alamat dan nama pengirim, serta toggle deteksi balasan (IMAP). Tekan **Kirim email tes** dan **Tes IMAP** setelah menyimpan.
- **WhatsApp & Pengiriman:** gateway (manual / Fonnte / Wablas), token, batas per jam & per hari tiap kanal, jam kirim, izin kirim ke nomor kantor, URL webhook untuk balasan, dan tombol kirim WhatsApp tes.
- **Blacklist:** email atau nomor yang tidak boleh dihubungi.

**WhatsApp gateway:** pakai nomor khusus bisnis, bukan nomor pribadi, dan mulai dengan batas kecil untuk nomor baru. Agar balasan dan kata "BERHENTI" terdeteksi otomatis, daftarkan URL webhook dari halaman Pengaturan di dashboard Fonnte/Wablas. Selama aplikasi masih lokal, URL itu hanya bisa dijangkau lewat tunnel (mis. Cloudflare Tunnel atau ngrok); tanpa tunnel, tandai balasan secara manual.

API key dan password disimpan terenkripsi dan tidak pernah ditampilkan kembali. Kolom rahasia yang dibiarkan kosong saat menyimpan tidak mengubah nilai yang tersimpan.

**Gmail:** aktifkan verifikasi 2 langkah, buat App Password di https://myaccount.google.com/apppasswords, lalu pakai host `smtp.gmail.com` port `587`. Untuk deteksi balasan, aktifkan juga IMAP di Gmail (Setelan → Penerusan dan POP/IMAP).

### Dari `.env` (infrastruktur)

| Variabel | Default | Keterangan |
|---|---|---|
| `DB_*` | | koneksi MySQL |
| `APP_URL` | | harus alamat publik (https) agar link unsubscribe di email bisa dibuka penerima; jika masih localhost, email berisi instruksi "balas BERHENTI" |
| `QUEUE_CONNECTION` | `database` | |
| `DB_QUEUE_RETRY_AFTER` | `1000` | harus lebih besar dari timeout job scraping (900 detik) |
| `SCRAPER_DRIVER` | `puppeteer` | `puppeteer`, `google_places`, atau `apify` |
| `SCRAPER_MAX_RESULTS` | `100` | maksimal bisnis per pencarian |
| `GOOGLE_PLACES_API_KEY` / `APIFY_TOKEN` | | hanya untuk driver terkait |
| `NODE_BINARY` | `node` | path `node.exe` jika tidak ada di PATH |
| `CHROME_PATH` | otomatis | lokasi Chrome standar Windows/macOS/Linux terdeteksi otomatis |
| `APP_TIMEZONE` / `APP_LOCALE` | `Asia/Jakarta` / `id` | zona waktu jadwal kirim & tampilan tanggal |
| `OUTREACH_HOURLY_LIMIT` / `OUTREACH_DAILY_LIMIT` | `20` / `80` | default batas email; bisa diubah di Pengaturan |
| `OUTREACH_MIN_GAP_SECONDS` / `OUTREACH_MAX_GAP_SECONDS` | `90` / `240` | jeda acak antar email |
| `OUTREACH_WINDOW_START` / `OUTREACH_WINDOW_END` / `OUTREACH_WEEKDAYS_ONLY` | `08:00` / `16:00` / `true` | default jam kirim semua kanal |
| `WA_DRIVER`, `WA_HOURLY_LIMIT`, `WA_DAILY_LIMIT` | `manual`, `10`, `50` | default WhatsApp; gateway & token diatur di Pengaturan |
| `AI_TIMEOUT` / `AI_RETRIES` | `120` / `2` | batas waktu dan retry panggilan AI (per provider) |
| `PAGESPEED_API_KEY` | | opsional, bisa juga diisi di Pengaturan |
| `ADMIN_PASSWORD` | `admin123` | password admin saat `db:seed` |
| `MAIL_MAILER` | `log` | hanya dipakai jika mode kirim belum dipilih di Pengaturan |

## Alur pemakaian

1. **Pengaturan:** ikuti checklist onboarding di Dashboard: identitas pengirim, koneksi AI, email, dan (opsional) WhatsApp gateway.
2. **Leads:** masukkan niche + kota (misalnya "klinik gigi" + "Surabaya") lalu jalankan scraping. Proses berjalan di background; notifikasi muncul di ikon lonceng saat selesai. Urutkan berdasarkan skor, lalu pakai aksi massal *Cari email* dan *Audit website* untuk lead yang punya website.
3. **Campaign:** buat campaign, pilih lead (manual atau *Smart Select*), pilih mode AI atau template, dan centang *auto generate*. Pesan AI dibuat di background dengan progress bar. Atur sequence follow-up di halaman detail campaign.
4. **Review:** buka detail campaign atau halaman Outreach. Pesan bertanda *Perlu review* tidak ikut antrean massal sampai diedit dan disimpan.
5. **Kirim:** pilih pesan lalu gunakan aksi massal *Kirim via antrean*. Email dan WhatsApp (jika gateway aktif) dikirim bertahap di jam kirim; tanpa gateway, WhatsApp membuka `wa.me` dengan pesan terisi.
6. **Lacak:** status dan kategori balasan berubah otomatis (IMAP untuk email, webhook untuk WhatsApp). Atur stage di halaman Pipeline, pantau Dashboard, dan baca laporan mingguan.

## Perintah artisan

| Perintah | Jadwal | Fungsi |
|---|---|---|
| `outreach:send-due` | tiap menit | kirim pesan antrean yang jatuh tempo (maks. 1 per kanal per putaran), mematuhi jam kirim dan batas per jam/hari |
| `outreach:retry-failed` | tiap 15 menit | jadwalkan ulang pesan yang gagal karena error sementara |
| `outreach:followups` | tiap jam | buat langkah sequence / follow-up berikutnya (Draft untuk direview, atau langsung antre jika diatur) |
| `outreach:check-replies` | tiap 10 menit | baca inbox via IMAP: balasan, bounce, balasan otomatis, permintaan berhenti |
| `outreach:check-drafts` | manual | jalankan quality gate ke draft AI yang sudah ada dan tandai yang bermasalah *Perlu review* (`--dry-run` untuk melihat dulu) |
| `leadhunter:weekly-report` | Senin 07.00 | kirim laporan 7 hari ke email admin (`--to=alamat` untuk uji coba) |
| `leadhunter:cleanup` | harian | hapus profil Chrome sisa scraping, notifikasi > 30 hari, log AI > 90 hari |
| heartbeat | tiap menit | tanda hidup scheduler & worker untuk banner peringatan |

Semua dijalankan oleh scheduler; bisa juga dijalankan manual, misalnya `php artisan outreach:check-replies --days=7`.

## Testing

```bash
php artisan test
```

Test memakai SQLite in-memory dan tidak pernah memanggil API sungguhan (`Http::preventStrayRequests()` aktif), jadi aman dijalankan kapan saja tanpa menyentuh database MySQL.

## Deploy

Panduan VPS Linux (Nginx, PHP-FPM, Supervisor untuk queue worker, cron untuk scheduler, checklist keamanan) ada di [docs/deployment.md](docs/deployment.md).

## Struktur folder

```txt
app/
├── Console/Commands/   perintah artisan (antrean kirim, follow-up, cek balasan, cleanup)
├── Exceptions/
├── Helpers/            Url (normalisasi + proteksi SSRF), Phone (format WhatsApp, seluler vs kantor), LeadSelection
├── Http/Controllers/
├── Jobs/               scraping, generate, kirim, enrich (crawl/audit), klasifikasi balasan, heartbeat
├── Mail/               email outreach, laporan mingguan
├── Models/             Setting menyimpan pengaturan (secret terenkripsi), CampaignStep, AiUsageLog
└── Services/
    ├── AiService.php            semua prompt & panggilan AI (provider cadangan, model cepat, log)
    ├── MessageQualityGate.php   pemeriksaan otomatis pesan AI
    ├── FollowupService.php      sequence follow-up per campaign
    ├── OutreachGenerator.php    buat pesan untuk banyak lead
    ├── OutreachSender.php       kirim email & WhatsApp, antrean, jam kirim
    ├── WebsiteAuditService.php  Google PageSpeed
    ├── SystemHealth.php         heartbeat worker & scheduler
    ├── WeeklyReport.php         isi laporan mingguan
    ├── RuntimeConfig.php        terapkan pengaturan dari database ke config
    ├── Replies/                 deteksi & klasifikasi balasan (IMAP, webhook WhatsApp)
    ├── WhatsApp/                gateway Fonnte / Wablas / manual
    └── Scraping/                driver Puppeteer / Google Places / Apify
config/leadhunter.php   konfigurasi scraper, pengiriman, WhatsApp, IMAP
scripts/                script Node (Puppeteer)
docs/                   dokumentasi deploy
```

Panduan untuk developer dan AI agent (konvensi kode, skema lengkap) ada di [agents.md](agents.md). Rencana pengembangan berikutnya ada di [roadmap.md](roadmap.md).

## Troubleshooting

| Masalah | Penyebab & solusi |
|---|---|
| Scraping tidak pernah selesai | Queue worker `scraping` tidak berjalan. Jalankan `composer run dev`. |
| Scraping berhenti setelah ±1 menit | Worker dijalankan tanpa `--timeout=0`. Pakai `composer run dev` atau tambahkan opsi itu. |
| Scraping gagal karena CAPTCHA | Google Maps membatasi pencarian dari IP Anda. Tunggu beberapa jam, kurangi frekuensi scraping, atau gunakan `SCRAPER_DRIVER=google_places`. |
| Scraping gagal "Tidak ada hasil" | Kata kunci terlalu spesifik. Coba kata kunci lebih umum. |
| Banner "worker/scheduler tidak berjalan" | Jalankan `composer run dev`, lalu cek job gagal di menu profil → Antrean & Worker. |
| WhatsApp tidak terkirim otomatis | Gateway masih mode manual, token salah, atau nomor lead adalah telepon kantor. Tekan *Kirim WA tes* di Pengaturan. |
| Pesan tidak ikut antrean massal | Pesan bertanda *Perlu review*. Edit dan simpan pesannya dulu. |
| Error "AI belum dikonfigurasi" | Isi Koneksi AI di halaman Pengaturan, lalu tekan *Tes AI*. |
| Email berstatus sent tapi tidak sampai | Mode kirim masih "log". Pilih SMTP di Pengaturan dan tekan *Kirim email tes*. |
| Gmail menolak login SMTP | Gunakan App Password (bukan password akun) dan pastikan verifikasi 2 langkah aktif. |
| API key/password tiba-tiba kosong | `APP_KEY` berubah. Isi ulang di halaman Pengaturan. |
| Pesan AI lama sekali | Kecepatan tergantung penyedia AI. Generate campaign berjalan di background, jadi halaman tidak perlu ditunggu. |
| `composer install` gagal soal versi PHP | PHP di terminal masih versi lama. Ganti ke 8.3 (lihat bagian Kebutuhan). |

## Catatan

Scraping DOM Google Maps rapuh terhadap perubahan tampilan dan melanggar Ketentuan Layanan Google. Untuk pemakaian serius, pertimbangkan driver `google_places` (API resmi) atau `apify`. Kirim cold email secukupnya, hormati permintaan berhenti, dan jangan melewati batas kirim harian penyedia email Anda.
