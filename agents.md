# AGENTS.md

## Project Name
LeadHunter AI (brand di UI: "Sandesa")

## Overview
LeadHunter AI adalah aplikasi sederhana untuk membantu freelancer, agency, dan digital marketer mencari calon client secara otomatis menggunakan scraping bisnis dan AI-generated outreach.

Fokus utama project:
- scraping bisnis
- mengambil email / nomor WhatsApp
- generate pesan outreach personal menggunakan AI
- mengirim outreach email (dengan antrean & batas kirim)
- tracking status outreach dan pipeline lead sederhana

Project ini dibuat dengan pendekatan MVP:
simple, cepat selesai, dan langsung usable.

---

# Goals

## Sudah berjalan
- [x] Cari bisnis berdasarkan niche + lokasi (Google Maps via Puppeteer, atau Google Places API / Apify)
- [x] Crawl website bisnis untuk email / nomor WhatsApp (dengan proteksi SSRF)
- [x] Generate outreach AI (Email & WhatsApp, Bahasa Indonesia) lewat queue, satu job per lead
- [x] Kirim email via SMTP (Gmail), langsung atau lewat antrean dengan batas per jam
- [x] WhatsApp click-to-chat (wa.me)
- [x] Status outreach: pending, queued, sent, failed, replied
- [x] Dashboard dengan angka nyata (sent rate, reply rate, failed rate, chart 30 hari, A/B template & mode)
- [x] Pengaturan identitas pengirim & penawaran default (tidak lagi hardcode)
- [x] Follow-up otomatis, unsubscribe link, blacklist
- [x] Deteksi reply otomatis via IMAP (opsional)
- [x] Pipeline lead (new → contacted → replied → meeting → deal / lost) + catatan per lead
- [x] Export/Import CSV
- [x] RBAC berbasis permission (Gate + middleware `can:`)

## Roadmap v2 (lihat roadmap.md)
- [x] Timezone WIB, heartbeat worker/scheduler + banner, halaman Antrean, `created_by`, onboarding, lupa password, CI
- [x] WhatsApp otomatis via gateway (Fonnte/Wablas/manual), deteksi nomor seluler vs kantor, webhook balasan
- [x] Skor lead + Hot, dedup place id, crawl & audit website massal (PageSpeed), composer AJAX, deteksi CAPTCHA
- [x] Sequence multi-langkah lintas kanal per campaign, jam kirim + batas harian, bounce, klasifikasi balasan, analitik per langkah & subjek
- [x] Log pemakaian AI, provider cadangan + model cepat, quality gate, varian prompt
- [x] Laporan mingguan via email
- [ ] Mode tim (assign lead, "milik saya") dan deploy production: saat dibutuhkan

## Non Goals (belum dibuat)
- microservices
- RabbitMQ
- realtime websocket
- AI multi-agent complex
- LinkedIn automation
- Instagram automation
- multi tenant SaaS
- AI memory
- RAG/vector database

---

# Tech Stack

## Backend
- Laravel 13
- PHP 8.3 (wajib ≥ 8.3; di Laragon pilih php-8.3.x)
- Pest 4 untuk test

## Frontend
- Blade + Tailwind CSS v4 (Vite)
- Dark mode: atribut `data-theme` di `<html>`, variant `dark:` sudah diikat ke atribut itu (`resources/css/app.css`)
- Chart.js (dashboard)

## Database
- MySQL (test memakai SQLite in-memory)

## Queue & Scheduler
- Queue driver `database`, dua antrean: `default` (AI, kirim email/WA, audit website, klasifikasi balasan) dan `scraping` (scraping & crawl, job panjang)
- Scheduler (`routes/console.php`): heartbeat, kirim antrean, retry, sequence follow-up, cek reply, laporan mingguan, cleanup
- `SystemHealth` mencatat heartbeat scheduler & tiap queue; layout menampilkan banner jika > 3 menit tidak ada tanda hidup
- Timezone aplikasi `Asia/Jakarta` (`APP_TIMEZONE`), locale `id`

## Pengaturan runtime
- Koneksi AI, SMTP/IMAP, WhatsApp gateway, batas & jam kirim disimpan di tabel `settings` (`App\Models\Setting`, secret di `Setting::SECRETS` terenkripsi) dan diterapkan ke config oleh `RuntimeConfig::apply()` saat boot dan sebelum setiap job. `.env` hanya default awal.
- Secret tidak pernah dirender ke view; field secret kosong saat simpan = tidak diubah.

## AI
- API OpenAI-compatible (Groq, 9router, OpenRouter, ...). Provider utama + provider cadangan opsional (dipakai otomatis saat utama gagal) + model cepat opsional untuk fitur di `AiService::FAST_FEATURES`.
- Semua prompt ada di `app/Services/AiService.php`. Jangan menulis prompt di controller.
- Setiap `generateText()`/`generateMany()` wajib memberi `['feature' => ...]` (kunci di `AiUsageLog::FEATURES`) supaya tercatat di log pemakaian.
- Pesan outreach AI melewati `MessageQualityGate`; gagal → ditulis ulang sekali → `needs_review`. Pesan `needs_review` tidak ikut antrean massal.
- Varian gaya pembuka (`AiService::PROMPT_VARIANTS`) disimpan di `outreach_messages.prompt_variant`.
- Tanpa API key, AI melempar `AiException` (tidak ada teks dummy)

## Email & WhatsApp
- Gmail SMTP (App Password). Mode `log` berarti email hanya ditulis ke log.
- Tampilan email (outreach, laporan mingguan) dan halaman unsubscribe memakai layout `resources/views/emails/layouts/brand.blade.php`, bergaya website perusahaan (lefateach.com). Palet & font di `App\Mail\EmailBrand`, identitas (nama, tagline, logo, telepon, website) dari Pengaturan. Style inline + tabel; email outreach juga punya bagian text/plain (`emails/outreach-text`). Pratinjau: `settings/email-preview`.
- Orb kaca 3D di email adalah PNG transparan (`public/images/email/orb-*.png`, `EmailBrand::ORBS`) yang disematkan sebagai gambar inline (CID) lewat `$message->embed()` di layout, karena klien email membuang blur/backdrop-filter/animasi. Halaman unsubscribe (browser) memakai CSS glass morphism asli.
- WhatsApp lewat `App\Services\WhatsApp\WhatsAppManager` (driver `manual`, `fonnte`, `wablas`). Kirim otomatis hanya ke nomor seluler (`leads.phone_is_mobile`) kecuali nomor kantor diizinkan.

## Scraping
- Default: Puppeteer (`scripts/scrape-gmaps.js`, `scripts/crawl-website.js`, helper bersama di `scripts/lib/browser.js`)
- Alternatif: `SCRAPER_DRIVER=google_places` atau `apify`
- Tidak ada "AI fallback" yang mengarang data bisnis. Jika scraping gagal, job gagal dan notifikasi "failed" muncul.

---

# Menjalankan Project

```bash
composer install
npm install
php artisan migrate --seed
composer run dev   # server + queue default + queue scraping + scheduler + vite
```

- Login default: `admin@leadhunter.com` / `admin123` (set `ADMIN_PASSWORD` sebelum seeding di server).
- `DB_QUEUE_RETRY_AFTER` harus > 900 (timeout job scraping).
- `composer run dev` memakai `queue:listen --timeout=0`; default 60 detik akan memotong job scraping.
- `php artisan test` untuk menjalankan test. Test tidak boleh memanggil API sungguhan (`Http::preventStrayRequests()` aktif).

---

# Core Features

## 1. Lead Scraping
Input: niche + lokasi, contoh "klinik gigi surabaya", "cafe bali".

Data: nama bisnis, alamat, website, telepon, email, rating, jumlah ulasan, kategori, URL Google Maps.

Alur: `LeadController@scrape` → `ScrapeGoogleMapsJob` (queue `scraping`) → `LeadScraperService` → `LeadSource` driver → setiap hasil langsung disimpan (`saveLead`: cocokkan `place_id` dulu, lalu nama + kota untuk lead lama tanpa place id).

Saat disimpan, `Lead` otomatis mengisi `whatsapp_number`, `phone_is_mobile`, `place_id` (dari URL Maps), dan `score` (`Lead::computeScore()`, Hot ≥ `Lead::HOT_SCORE`). Aksi massal crawl/audit memakai `EnrichLeadJob` dalam `Bus::batch`; pilihan "semua hasil filter" lewat `App\Helpers\LeadSelection`.

## 2. AI Outreach Generator
Pesan harus: pendek, natural, tidak terlalu salesy, personalized (memakai data lead: bidang, kota, rating, ada/tidaknya website).

- Campaign / bulk generate: `OutreachGenerator` → `GenerateOutreachJob` per lead per channel, progres di `campaigns.generation_*`
- Composer (preview interaktif): panggilan AI paralel via `AiService::generateMany`
- Mode: `template` (render instan), `ai`, `hybrid` (template dipoles AI)

## 3. Outreach Sender
- `OutreachSender::send()` (→ `sendEmail` / `sendWhatsApp`) adalah satu-satunya jalur kirim: menolak pesan yang sudah terkirim, cek blacklist, hentikan langkah sequence jika lead sudah membalas/ditutup (`Lead::sequenceStopReason()`), set `message_id`, `sent_at`, dan majukan stage lead
- Bulk "Kirim via antrean": status `queued` + `scheduled_at` per kanal dengan jeda acak, digeser ke jam kirim; `outreach:send-due` mengirim maks. 1 pesan per kanal per putaran dan mematuhi batas per jam & per hari
- Link unsubscribe hanya dipasang jika `APP_URL` publik; jika lokal, email berisi instruksi "balas BERHENTI"

## 4. Sequence & balasan
- `campaign_steps` = langkah 2 dst. per campaign (kanal `same|email|whatsapp`, jeda hari, `auto_queue`). Campaign tanpa langkah memakai satu follow-up default dari Pengaturan. Logika di `FollowupService`.
- `ReplyDetector` (IMAP) dan `WhatsAppReplyHandler` (webhook): bounce → gagal + blacklist "bounce"; BERHENTI → blacklist + batalkan semua pesan terbuka di semua kanal; balasan otomatis → `reply_category = auto_reply` tanpa dihitung reply; balasan asli → `replied` + `ClassifyReplyJob` (`ReplyClassifier`: aturan → AI → kata kunci).

## 5. Dashboard
Total leads, total outreach, sent rate, reply rate, chart harian, per campaign, per channel, A/B per template, mode, varian prompt, langkah sequence, pola subjek, onboarding, antrean kirim.

---

# Database Structure (ringkas)

## leads
id, business_name, niche, category, website, email, phone, whatsapp_number, phone_is_mobile, address, city, source, pipeline_stage, rating, reviews_count, google_maps_url, place_id (unique), score, website_score, website_https, website_audited_at, created_by, timestamps. Index (business_name, city), bukan unique.

## campaigns
id, name, niche, location, generation_total, generation_done, generation_failed, created_by, timestamps

## campaign_steps
id, campaign_id, step (2..5), channel (same|email|whatsapp), delay_days, auto_queue, timestamps

## outreach_messages
id, lead_id, campaign_id, step, type (email|whatsapp), subject, message, status (pending|queued|sent|failed|replied), scheduled_at, attempts, last_error, message_id, mode (template|ai|hybrid), needs_review, prompt_variant, template_id, followup_of_id, sent_at, replied_at, reply_category, reply_excerpt, created_by, timestamps. Label UI status: `OutreachMessage::STATUS_LABELS`.

## Lainnya
- `message_templates`: template per niche/channel dengan placeholder `{{business_name}}`, `{{city}}`, `{{niche}}`, `{{website}}`, `{{phone}}`, `{{offer}}`, `{{sender_name}}`, `{{company_name}}`, `{{company_website}}`
- `settings`: key/value (identitas, penawaran, follow-up, koneksi AI/email/WA, batas kirim). Akses lewat `App\Models\Setting`
- `ai_usage_logs`: satu baris per panggilan AI (feature, provider, model, durasi, token, error)
- `blacklist_entries`: email/telepon yang tidak boleh dihubungi
- `lead_notes`: catatan per lead
- `scraping_notifications`: notifikasi lonceng (scraping, generate, follow-up, reply)
- `users`, `roles`, `permissions`, `role_user`, `permission_role`: RBAC

---

# Folder Structure

```txt
app/
├── Console/Commands/     # outreach:send-due, outreach:retry-failed, outreach:followups, outreach:check-replies, leadhunter:weekly-report, leadhunter:cleanup
├── Exceptions/           # AiException, ScrapeFailedException, CrawlException, OutreachSendException
├── Helpers/              # Url (normalisasi + SSRF guard), Phone (WhatsApp, seluler vs kantor), LeadSelection
├── Http/
│   ├── Controllers/      # termasuk QueueController, WhatsAppWebhookController, Auth/PasswordResetController
│   └── Middleware/
├── Jobs/                 # ScrapeGoogleMaps, GenerateOutreach, SendOutreach, EnrichLead, ClassifyReply, Heartbeat
├── Mail/                 # OutreachMail, WeeklyReportMail, EmailBrand (palet & identitas email)
├── Models/               # + CampaignStep, AiUsageLog, Concerns/RecordsCreator
├── Providers/            # Gate RBAC, RuntimeConfig, heartbeat queue
└── Services/
    ├── AiService.php
    ├── MessageQualityGate.php
    ├── OutreachGenerator.php
    ├── OutreachSender.php
    ├── FollowupService.php
    ├── WebsiteCrawlerService.php, WebsiteAuditService.php
    ├── SystemHealth.php, RuntimeConfig.php, WeeklyReport.php, NodeScriptRunner.php
    ├── Replies/          # ReplyDetector, ReplyClassifier, WhatsAppReplyHandler, ImapMailbox
    ├── WhatsApp/         # WhatsAppGateway, Fonnte, Wablas, Manual, WhatsAppManager
    └── Scraping/         # LeadScraperService, LeadSource + driver Puppeteer / Google Places / Apify

config/leadhunter.php     # scraper, node/chrome, batas & jam kirim, WhatsApp, IMAP
scripts/                  # script Node (Puppeteer)
resources/views/
routes/
├── web.php
└── console.php           # scheduler
docs/deployment.md        # panduan deploy VPS
```

---

# Konvensi

- Teks UI dan pesan flash dalam Bahasa Indonesia (satu bahasa, tanpa Google Translate). Tanggal pakai `translatedFormat()`.
- Waktu disimpan dan ditampilkan dalam WIB (`APP_TIMEZONE`); jangan hardcode UTC.
- Model yang dibuat user memakai trait `RecordsCreator` (`created_by` otomatis).
- Permission dicek dengan `can:<slug>` di route dan `@can` di Blade. Daftar slug ada di `Permission::SLUGS`.
- Data dari luar (hasil scraping, output AI) wajib di-escape. Di JS gunakan `window.escapeHtml()` sebelum `innerHTML`. Jangan menaruh data lead di dalam atribut `onclick`; pakai `data-*` atau `@js()`.
- Semua URL website lead lewat `App\Helpers\Url::normalize()`.
- Jangan memanggil `env()` di luar folder `config/`.
