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
- Queue driver `database`, dua antrean: `default` (AI, email) dan `scraping` (job panjang)
- Scheduler (`routes/console.php`): kirim antrean email, retry, follow-up, cek reply, cleanup

## AI
- API OpenAI-compatible (Groq, 9router, OpenRouter, ...) lewat `AI_BASE_URL`, `AI_API_KEY`, `AI_MODEL`
- Semua prompt ada di `app/Services/AiService.php`. Jangan menulis prompt di controller.
- Tanpa API key, AI melempar `AiException` (tidak ada lagi teks dummy)

## Email
- Gmail SMTP (App Password). `MAIL_MAILER=log` berarti email hanya ditulis ke log.

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

Alur: `LeadController@scrape` → `ScrapeGoogleMapsJob` (queue `scraping`) → `LeadScraperService` → `LeadSource` driver → setiap hasil langsung disimpan (`saveLead`, dedup nama bisnis + kota).

## 2. AI Outreach Generator
Pesan harus: pendek, natural, tidak terlalu salesy, personalized (memakai data lead: bidang, kota, rating, ada/tidaknya website).

- Campaign / bulk generate: `OutreachGenerator` → `GenerateOutreachJob` per lead per channel, progres di `campaigns.generation_*`
- Composer (preview interaktif): panggilan AI paralel via `AiService::generateMany`
- Mode: `template` (render instan), `ai`, `hybrid` (template dipoles AI)

## 3. Outreach Sender
- `OutreachSender::sendEmail` adalah satu-satunya jalur kirim email: menolak pesan yang sudah terkirim, cek blacklist, set `message_id`, `sent_at`, dan majukan stage lead
- Bulk "Kirim via antrean": status `queued` + `scheduled_at` dengan jeda acak; scheduler `outreach:send-due` mematuhi `OUTREACH_HOURLY_LIMIT`
- Link unsubscribe hanya dipasang jika `APP_URL` publik; jika lokal, email berisi instruksi "balas BERHENTI"

## 4. Dashboard
Total leads, total outreach, sent rate, reply rate, failed rate, chart harian, per campaign, per channel, A/B per template & mode, antrean kirim.

---

# Database Structure (ringkas)

## leads
id, business_name, niche, category, website, email, phone, address, city, source, pipeline_stage, rating, reviews_count, google_maps_url, timestamps. Unique: (business_name, city).

## campaigns
id, name, niche, location, generation_total, generation_done, generation_failed, timestamps

## outreach_messages
id, lead_id, campaign_id, type (email|whatsapp), subject, message, status (pending|queued|sent|failed|replied), scheduled_at, attempts, last_error, message_id, mode (template|ai|hybrid), template_id, followup_of_id, sent_at, replied_at, timestamps

## Lainnya
- `message_templates`: template per niche/channel dengan placeholder `{{business_name}}`, `{{city}}`, `{{niche}}`, `{{website}}`, `{{phone}}`, `{{offer}}`, `{{sender_name}}`, `{{company_name}}`, `{{company_website}}`
- `settings`: key/value (identitas pengirim, penawaran default, follow-up). Akses lewat `App\Models\Setting`
- `blacklist_entries`: email/telepon yang tidak boleh dihubungi
- `lead_notes`: catatan per lead
- `scraping_notifications`: notifikasi lonceng (scraping, generate, follow-up, reply)
- `users`, `roles`, `permissions`, `role_user`, `permission_role`: RBAC

---

# Folder Structure

```txt
app/
├── Console/Commands/     # outreach:send-due, outreach:retry-failed, outreach:followups, outreach:check-replies, leadhunter:cleanup
├── Exceptions/           # AiException, ScrapeFailedException, CrawlException, OutreachSendException
├── Helpers/              # Url (normalisasi + SSRF guard), Phone (format WhatsApp)
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Jobs/                 # ScrapeGoogleMapsJob, GenerateOutreachJob, SendOutreachJob
├── Mail/                 # OutreachMail
├── Models/
├── Providers/            # Gate RBAC
└── Services/
    ├── AiService.php
    ├── OutreachGenerator.php
    ├── OutreachSender.php
    ├── FollowupService.php
    ├── NodeScriptRunner.php
    ├── WebsiteCrawlerService.php
    ├── Replies/          # ReplyDetector, ImapMailbox
    └── Scraping/         # LeadScraperService, LeadSource + driver Puppeteer / Google Places / Apify

config/leadhunter.php     # scraper, node/chrome, batas kirim, IMAP
scripts/                  # script Node (Puppeteer)
resources/views/
routes/
├── web.php
└── console.php           # scheduler
docs/deployment.md        # panduan deploy VPS
```

---

# Konvensi

- Teks UI dan pesan flash dalam Bahasa Indonesia.
- Permission dicek dengan `can:<slug>` di route dan `@can` di Blade. Daftar slug ada di `Permission::SLUGS`.
- Data dari luar (hasil scraping, output AI) wajib di-escape. Di JS gunakan `window.escapeHtml()` sebelum `innerHTML`. Jangan menaruh data lead di dalam atribut `onclick`; pakai `data-*` atau `@js()`.
- Semua URL website lead lewat `App\Helpers\Url::normalize()`.
- Jangan memanggil `env()` di luar folder `config/`.
