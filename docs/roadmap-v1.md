# LeadHunter AI: Roadmap v1 (arsip)

> Fase 0–4, sudah dieksekusi pada 2026-09-27. Roadmap aktif ada di [../roadmap.md](../roadmap.md).

> Audit kode per **2026-09-27**. Bagian A–C di bawah adalah hasil audit **sebelum** roadmap dieksekusi.

## Status eksekusi (2026-09-27)

Test: 99 test Pest lulus (`php artisan test`). Migration baru sudah dijalankan di DB lokal; backup sebelum migrasi ada di `storage/app/backups/`.

| Fase | Status | Catatan |
|---|---|---|
| 0. Stabilisasi & keamanan | ✅ Selesai | Config AI via `AI_*` (OpenAI-compatible, termasuk 9router), path Node/Chrome dari env, AI fallback pengarang bisnis dihapus, notifikasi gagal benar, XSS & injeksi `onclick` diperbaiki, notifikasi via POST, throttle login, proteksi SSRF, integritas data campaign/kirim, seeder idempotent, test hijau, `AGENTS.md` diperbarui. |
| 1. Dashboard real & kualitas data | ✅ Selesai | Dashboard dari data nyata, rating/kategori/URL Maps, unique index + merge duplikat, filter kontak, halaman Pengaturan, prompt disatukan di `AiService`, export/import CSV. |
| 2. Otomasi outreach | ✅ Selesai | `SendOutreachJob`, bulk send via antrean (limit/jam + jeda acak), generate AI per lead di queue dengan progress bar, scheduler, follow-up otomatis, unsubscribe + blacklist. |
| 3. Deteksi reply & insight | ✅ Sebagian besar | IMAP reply detection (termasuk balasan "BERHENTI"), pipeline + catatan, A/B template & mode, RBAC permission ditegakkan. **Belum:** kolom `user_id`/ownership (roadmap menyebut "jika dipakai lebih dari satu orang"). IMAP belum diuji ke server Gmail sungguhan. |
| 4. Deployment & ketahanan scraping | ✅ Kode / 📄 Dokumen | Driver Google Places API & Apify di balik interface `LeadSource`. Panduan VPS (Nginx, Supervisor, cron, checklist keamanan) di `docs/deployment.md`; deploy-nya sendiri belum dilakukan. |

Temuan tambahan saat eksekusi:
- `queue:listen` bawaan membunuh job setelah 60 detik, jadi scraping lewat `composer run dev` dulu terpotong. Sekarang memakai `--timeout=0` dengan worker `scraping` terpisah.
- Template di DB lokal punya 4 salinan per template (akibat seeder lama). Datanya tidak diubah; hapus manual jika perlu.
- `composer audit`: 37 advisory di 11 paket (sudah ada di lock file sebelumnya). Jalankan `composer update` + test sebelum deploy.

---

Catatan audit awal: `AGENTS.md` saat itu tidak akurat di beberapa hal (sekarang sudah diperbarui).
- Framework-nya **Laravel 13** (`composer.json`), bukan Laravel 11.
- Tailwind-nya **v4**, bukan v3.
- Scraping memakai **Puppeteer** (node script di `scripts/`), bukan Apify.

---

## A. Status fitur saat ini

Legenda: ✅ berjalan · ⚠️ ada tapi bermasalah/parsial · ❌ belum ada

| Modul | Status | Catatan |
|---|---|---|
| Login / Logout | ✅ | Belum ada throttling brute-force. Halaman register ada, tapi tidak punya route (dead code). |
| RBAC (roles, permissions, admin users/roles CRUD) | ⚠️ | Hanya `role:admin` di `/admin/*` yang ditegakkan. 6 permission hasil seed tidak pernah dicek. |
| Leads CRUD, search, filter "tanpa website", pagination 20 | ✅ | Tidak ada cek duplikat saat tambah manual. |
| Scraping Google Maps | ✅/⚠️ | Alur: `ScrapeGoogleMapsJob` (queue database) → `scripts/scrape-gmaps.js` (Puppeteer) → maks. 100 tempat, lalu crawl email/telepon dari website. **Masalah:** rating & kategori tidak disimpan; path Windows di-hardcode (`GoogleMapsScraperService.php:20-34`); "AI fallback" rusak dan berisiko menyimpan bisnis fiktif. |
| Crawl website per lead (cari email/WA) | ⚠️ | Jalan sinkron di request HTTP dengan timeout 40 detik, sehingga rawan error 500. Rawan SSRF karena URL-nya bebas. |
| Notifikasi scraping (bell, polling 5 detik) | ⚠️ | Aksi `mark-read`/`clear` lewat GET. Ada **XSS** di `layouts/app.blade.php:648-649`. |
| Campaign CRUD + auto-generate pesan (email/WA, mode AI/template) | ⚠️ | Offer & sender di-hardcode ("Jasa Pembuatan Website", "Thoriq dari Lefateach"). `update` bisa **menghapus riwayat sent/replied** milik lead yang di-unselect (`CampaignController.php:384-387`). |
| AI Smart Matching (auto-select leads) | ✅/⚠️ | Filter keyword, lalu Groq. Tanpa API key, hasilnya hanya keyword match. |
| Outreach Composer (template / AI / hybrid, preview, polish, save) | ✅ | Fitur paling matang. |
| Bulk generate dari tabel Leads | ⚠️ | Satu kegagalan Groq menghentikan seluruh loop. |
| Message Templates (CRUD, toggle, placeholder `{{business_name}}` dll) | ✅ | Seeder template tidak idempotent (jadi duplikat jika di-seed ulang). |
| Kirim email (SMTP) | ⚠️ | Sinkron, tanpa queue. Belum ada bulk send. Pesan yang sudah terkirim bisa dikirim ulang. Dengan default `MAIL_MAILER=log`, email hanya masuk log tapi statusnya tetap jadi "sent". |
| WhatsApp click-to-chat (`wa.me`) | ✅ | Normalisasi nomor masih sederhana (0 → 62). |
| Status tracking (pending/sent/failed/replied) | ✅/⚠️ | "Replied" diisi **manual** (tidak ada pembacaan IMAP/inbox). Mengubah status ke "sent" secara manual tidak mengisi `sent_at`. |
| Dashboard | ⚠️ | Hanya **total leads** & **outreach sent** yang real. Skor 92%/88%/24.8% dan chart masih **mock hardcoded**. Sent rate & reply rate belum dihitung. |
| Multi-bahasa (ID/EN) | ⚠️ | Memakai widget Google Translate. File `lang/*.json` hanya berisi 12 key. |
| Dark mode | ⚠️ | Toggle tidak sinkron dengan class `dark:` Tailwind v4. |
| AI Groq | ⚠️ | Model `llama-3.1-8b-instant`. Key dibaca langsung via `env()`, jadi hilang jika `config:cache`. **Tanpa `GROQ_API_KEY`, semua fitur AI diam-diam mengembalikan teks dummy bahasa Inggris.** |
| Tests (Pest 4) | ⚠️ | Belum dijalankan. Dari pembacaan kode, 6 dari 8 test kemungkinan gagal karena tidak login (`actingAs()`). |
| Export CSV, scheduler, deteksi reply otomatis, follow-up | ❌ | Belum ada. |

---

## B. Cara menjalankan project (Windows, lokal)

### Prasyarat
- PHP ≥ 8.3 dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `dom`. Untuk test juga perlu `pdo_sqlite`.
- Composer 2.
- Node ≥ 22.12 (dibutuhkan Puppeteer 25 & Vite 8).
- MySQL di `127.0.0.1:3306`.
- Google Chrome di `C:\Program Files\Google\Chrome\Application\chrome.exe`.

### Langkah
```bash
cp .env.example .env
# set di .env: DB_DATABASE=leadhunter, DB_USERNAME=root, DB_PASSWORD= (kosong)

composer install
php artisan key:generate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS leadhunter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
npm install                               # .npmrc: ignore-scripts=true
# npx puppeteer browsers install chrome   # hanya jika Chrome sistem tidak ada
composer run dev                          # serve + 2 queue worker + scheduler + vite
```

Alternatifnya, jalankan 5 terminal terpisah:
- `php artisan serve`
- `php artisan queue:listen --queue=default --tries=1 --timeout=0`
- `php artisan queue:listen --queue=scraping --tries=1 --timeout=0`
- `php artisan schedule:work`
- `npm run dev`

Buka http://127.0.0.1:8000.

### Login default (dari seeder, ganti sebelum deploy)
- Admin: `admin@leadhunter.com` / `admin123`
- User: `test@example.com` / `password`

### Tambahkan ke `.env` agar fitur real berjalan
```env
AI_BASE_URL=https://api.groq.com/openai/v1   # atau router lain yang OpenAI-compatible
AI_API_KEY=xxx                       # tanpa ini fitur AI menampilkan error yang jelas
AI_MODEL=llama-3.1-8b-instant
DB_QUEUE_RETRY_AFTER=1000            # job scrape timeout 900s > default 90s
APP_NAME="LeadHunter AI"
APP_URL=http://127.0.0.1:8000
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=emailanda@gmail.com
MAIL_PASSWORD=<16 digit App Password Gmail, wajib 2FA>
MAIL_FROM_ADDRESS=emailanda@gmail.com
```

### Jebakan setup
- Queue worker **wajib jalan**. Tanpa worker, scraping tidak pernah dieksekusi.
- `config:cache` sekarang aman: semua `env()` sudah dipindah ke folder `config/`.
- Sebaiknya tidak serve dari subfolder (misalnya XAMPP `/leadhunter/public`); sebagian besar URL di JS sudah memakai `route()`, tapi belum semuanya dicek.
- Jalankan `composer install` sebelum `npm run dev/build`, karena `app.css` meng-import file dari `vendor/`.
- Path Node & Chrome diatur lewat `NODE_BINARY` / `CHROME_PATH` (Chrome di lokasi standar terdeteksi otomatis).
- `php artisan serve` di Windows hanya satu thread: selama satu request panjang berjalan (misalnya preview AI), request lain menunggu. Untuk pemakaian harian, pakai virtual host Laragon (Apache).

---

## C. Roadmap

Tetap mengikuti prinsip MVP dan non-goals di `AGENTS.md`: tanpa microservices, websocket, multi-tenant, atau RAG.

### Fase 0: Stabilisasi & Keamanan (prioritas, ±1 minggu)
1. **Config Groq & scraper**
   - Tambah blok `groq` di `config/services.php` (`key`, `model`), lalu ganti `env()` di `GroqApiService.php:14` dan `GoogleMapsScraperService.php:160` jadi `config()`.
   - Tambah `GROQ_API_KEY`, `DB_QUEUE_RETRY_AFTER`, `NODE_BINARY`, `CHROME_PATH` ke `.env.example`.
   - Tambahkan timeout + retry dan tangani respons `null` di `GroqApiService`.
   - Jika key kosong, **lempar error yang jelas**, jangan kembalikan teks dummy.
2. **Hapus hardcode path Windows**
   - `GoogleMapsScraperService.php:20-34` dan `LeadController.php:126-129`: baca path Node dari config/env, dan jangan teruskan `$_SERVER`/`$_ENV` (yang berisi secret) ke proses Node.
   - Path Chrome di `scripts/*.js` dibaca dari env `CHROME_PATH`.
3. **Perbaiki bug scraping**
   - Hapus atau perbaiki AI fallback yang mengarang bisnis (`GoogleMapsScraperService.php:121-200`).
   - Notifikasi "failed" harus muncul saat Puppeteer gagal atau timeout.
   - Perbaiki cleanup `uniqueProfile` (deklarasikan di luar `try`) di `scrape-gmaps.js:14` & `crawl-website.js:16`.
4. **Keamanan**
   - Escape `item.title`/`item.message` di `layouts/app.blade.php:648-649` (XSS).
   - Ubah `scrape-status?action=clear|mark-read` menjadi route POST.
   - Tambah `throttle` pada `POST /login`.
   - Validasi URL di `crawlWebsite` (hanya http/https, tolak IP privat).
   - Perbaiki injeksi JS di `onclick` pada `campaigns/show.blade.php:378`.
5. **Integritas data**
   - `CampaignController::update` jangan menghapus pesan berstatus sent/replied.
   - `OutreachController::generate` tetap lanjut ke lead berikutnya saat satu gagal (kumpulkan error-nya).
   - Kirim email harus menolak pesan yang sudah `sent`.
   - Isi `sent_at` saat status diubah manual ke sent.
6. **Bug kecil**
   - Route `show` yang error 500: pakai `->except('show')` pada resource users/roles/templates.
   - Ubah seeder template ke `updateOrCreate`.
7. **Tests**
   - Tambah `actingAs()` di test yang ada supaya hijau.
   - Tambah test untuk scrape dispatch (`Queue::fake`), kirim email (`Mail::fake`), dan RBAC.
8. **Update `AGENTS.md`**: Laravel 13, Tailwind v4, Puppeteer, skema tabel terbaru.

### Fase 1: Dashboard real & kualitas data (±1–2 minggu)
- **Dashboard real**
  - Hitung sent rate (sent/total), reply rate (replied/sent), dan failed rate dari `outreach_messages`.
  - Chart harian 30 hari (sent vs replied) menggantikan mock di `dashboard.blade.php`.
  - Tampilkan `recentOutreach` dan statistik per campaign.
- **Kualitas leads**
  - Simpan `rating`, `category`, `google_maps_url` (migration baru; datanya sudah ada di output script).
  - Tambah unique index untuk dedup.
  - Filter leads berdasarkan ada/tidaknya email, WA, dan website.
- **Pengaturan sender:** halaman Settings (nama pengirim, nama usaha, offer default, signature). Ini menggantikan hardcode "Thoriq dari Lefateach" & "Jasa Pembuatan Website" di `CampaignController`, `OutreachController`, dan `emails/outreach.blade.php`.
- **Satukan prompt AI** (versi store/update/generate masih berbeda-beda) ke satu tempat di `GroqApiService`.
- **Export/Import CSV** untuk leads & hasil outreach.

### Fase 2: Otomasi outreach (±2 minggu)
- **Kirim email via queue:** `OutreachMail implements ShouldQueue`, atau buat job `SendOutreachJob`.
- **Bulk send** dengan rate limit (misalnya 20 email/jam, aman untuk limit Gmail) dan jeda acak.
- **Generate AI via queue** (satu job per lead) supaya request HTTP tidak timeout dan tidak kena error 429 dari Groq. Progres ditampilkan lewat polling yang sudah ada.
- **Scheduler** (`routes/console.php`): jalankan antrean kirim terjadwal, bersihkan folder profil Chrome, retry pesan yang failed.
- **Follow-up otomatis:** jika belum replied setelah N hari, generate satu pesan follow-up.
- **Unsubscribe link + blacklist** email/nomor, demi etika & reputasi domain.

### Fase 3: Deteksi reply & insight (±2–3 minggu)
- **Deteksi reply otomatis via IMAP Gmail:** cocokkan balasan dengan pesan terkirim, lalu set status `replied`.
- **Pipeline sederhana per lead:** new → contacted → replied → meeting → deal, plus catatan per lead.
- **A/B test template:** bandingkan reply rate per template dan per mode (AI vs template).
- **RBAC tegas:** terapkan `hasPermission()` di route, dan tambah `user_id` (ownership) jika aplikasi dipakai lebih dari satu orang. Ini bukan multi-tenant.

### Fase 4 (opsional): Deployment & ketahanan scraping
- Opsi scraper alternatif via **Apify / Google Places API** di balik interface yang sama. DOM scraping Google Maps rapuh dan melanggar ToS Google.
- Deploy ke VPS Linux:
  - Supervisor untuk queue worker.
  - Cron untuk `schedule:run`.
  - Chrome headless di server.
  - `APP_DEBUG=false`.
  - Ganti password admin default.

---

## Lampiran: file yang disentuh Fase 0
- `config/services.php`, `.env.example`
- `app/Services/GroqApiService.php`, `app/Services/GoogleMapsScraperService.php`
- `app/Http/Controllers/LeadController.php`, `CampaignController.php`, `OutreachController.php`
- `scripts/scrape-gmaps.js`, `scripts/crawl-website.js`
- `resources/views/layouts/app.blade.php`, `resources/views/campaigns/show.blade.php`
- `routes/web.php`, `database/seeders/MessageTemplateSeeder.php`
- `tests/Feature/*.php`, `AGENTS.md`
