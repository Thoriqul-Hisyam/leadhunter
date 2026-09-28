# LeadHunter AI: Roadmap v2

> Disusun 2026-09-27, setelah roadmap v1 (Fase 0–4) selesai dieksekusi. Arsip v1 beserta status eksekusinya ada di [docs/roadmap-v1.md](docs/roadmap-v1.md).

Tetap mengikuti prinsip MVP dan non-goals di [agents.md](agents.md): tanpa microservices, websocket, multi-tenant, atau RAG.

## Kondisi saat ini

- 113 test Pest lulus. Scraper Google Maps, crawler website, AI (lewat 9router), dan halaman utama sudah diuji ke layanan sungguhan.
- Koneksi AI dan email diatur dari halaman Pengaturan (tersimpan di database, secret terenkripsi).
- Semua pekerjaan v1 **belum di-commit**.
- Keputusan arah: aplikasi dipakai sendiri dulu (mungkin tim nanti), WhatsApp akan dikirim otomatis lewat gateway, dan app masih berjalan lokal.

## Status eksekusi (2026-09-28)

Fase 5–9 dan 10.2 sudah dikerjakan dan belum di-commit (commit dilakukan sendiri oleh pemilik repo). 185 test Pest lulus; migration `2026_09_28_000001` s.d. `000008` sudah dijalankan di database lokal (backup sebelum migrasi: `storage/app/backups/`). Uji nyata lewat 9router: generate pesan lolos quality gate, klasifikasi 3 contoh balasan tepat semua. Quality gate menandai 29 dari 30 draft lama (menyebut rating/ulasan/alamat, frasa "kehadiran digital").

| Item | Status | Catatan |
|---|---|---|
| 5.1 CI | Dibuat | `.github/workflows/ci.yml` (test, build, `composer audit`). Status hijau baru terlihat setelah push. |
| 5.2 Advisory | Selesai | Laravel 13.33, `composer audit` bersih. |
| 5.3–5.7, 5.10 | Selesai | Timezone WIB (data lama dikonversi), heartbeat + banner, halaman Antrean & Worker, `created_by`, checklist onboarding, lupa password. |
| 5.8 UI satu bahasa | Selesai | Locale `id` (+ `lang/id` untuk pagination & validasi); label status: Draft, Antre, Terkirim, Gagal, Dibalas. |
| 5.9 IMAP ke Gmail sungguhan | **Perlu pemilik** | Butuh App Password Gmail. Isi di Pengaturan → Koneksi, lalu tekan *Tes IMAP*. |
| 6.1–6.8 WhatsApp gateway | Selesai, **perlu uji nyata** | Diuji dengan HTTP palsu. Butuh token Fonnte/Wablas untuk *Kirim WA tes*; webhook butuh tunnel selama lokal. |
| 7.1–7.6 | Selesai | 90 lead lokal: 90 place id tanpa duplikat, 57 nomor seluler, 28 lead Hot. Audit PageSpeed tanpa key kena kuota bersama Google, jadi **isi PageSpeed API key**. |
| 8.1–8.5 | Selesai | Sequence per campaign (maks. 4 langkah lanjutan), bounce, balasan otomatis, klasifikasi balasan, analitik per langkah & pola subjek. 8.2 sudah dikerjakan di Fase 6. |
| 9.1–9.4 | Selesai | Log AI + ringkasan 7 hari, provider cadangan + model cepat, quality gate + *Perlu review*, dua varian gaya pembuka. |
| 10.2 Laporan mingguan | Selesai | `leadhunter:weekly-report`, Senin 07.00, bisa dimatikan di Pengaturan. |
| 10.1 Mode tim, 10.3 Deploy | Ditunda | Sesuai rencana: dikerjakan saat dibutuhkan. |

Metrik sukses di bawah belum diukur ulang karena butuh pemakaian nyata (crawl massal, gateway WhatsApp, dan balasan sungguhan).

## Temuan yang mendasari roadmap ini

Dicek langsung di kode dan database lokal (90 lead hasil scraping "Klinik Kecantikan Surabaya"):

| # | Temuan | Dampak |
|---|---|---|
| 1 | Hanya 32 dari 90 lead (36%) punya email. Dari 87 lead yang punya telepon, 57 nomor seluler dan 30 nomor kantor (mis. `(031) 99429586`). | **38 lead (42%) hanya bisa dijangkau lewat WhatsApp.** Link `wa.me` ke nomor kantor tidak akan berfungsi. |
| 2 | AI lewat 9router butuh 40–120 detik per pesan dan menyisipkan ±2.800 token system prompt; pernah memunculkan artefak "→ skipped: …". | Tidak ada data latensi/biaya, tidak ada provider cadangan saat router lambat atau mati. |
| 3 | Scraping, generate AI, antrean kirim, follow-up, dan cek balasan semuanya bergantung pada queue worker + scheduler, tapi UI tidak memberi tahu jika keduanya mati. `failed_jobs` tidak tampil di UI. | Masalah seperti "scraping terpotong 60 detik" hanya ketahuan dari log. |
| 4 | `config/app.php` memakai `'timezone' => 'UTC'`. | Jadwal kirim, dashboard, dan notifikasi selisih 7 jam dari WIB. |
| 5 | Setelah Google Translate dihapus, UI campur Inggris/Indonesia ("Hunt New Leads", "All Leads", "Create Outreach Template"). | Tampilan tidak konsisten. |
| 6 | `OutreachController@index` memuat `Lead::all()` untuk composer. | Halaman Outreach akan lambat saat lead mencapai ribuan. |
| 7 | Dedup lead memakai nama bisnis + kota. | Cabang (chain) dengan nama sama tergabung menjadi satu lead. |
| 8 | Tidak ada CI; `composer audit` menemukan 37 advisory di 11 paket; IMAP belum diuji ke Gmail sungguhan; belum ada "lupa password". | Risiko regresi dan keamanan sebelum deploy. |
| 9 | Hanya satu follow-up; tidak ada jam kirim; bounce tidak terdeteksi; balasan otomatis (out of office) dihitung sebagai reply. | Reply rate bisa menyesatkan dan pola kirim terlihat seperti bot. |

---

## Fase 5: Fondasi & operasional (±1 minggu, prioritas tertinggi)

**Kenapa:** temuan 3, 4, 5, 8. Semua fase berikutnya, terutama WhatsApp otomatis, bergantung pada worker yang terpantau dan waktu yang benar.

| # | Yang dibuat | Selesai jika | File utama |
|---|---|---|---|
| 5.1 | GitHub Actions CI (commit pekerjaan v1 dilakukan sendiri oleh pemilik repo) | Setiap push menjalankan `php artisan test` dan `npm run build`, dan statusnya hijau | `.github/workflows/ci.yml` |
| 5.2 | Update paket yang punya advisory | `composer audit` bersih (atau sisa advisory tercatat alasannya), semua test hijau | `composer.json`, `composer.lock` |
| 5.3 | Timezone `Asia/Jakarta` lewat `APP_TIMEZONE` | Jadwal kirim, dashboard, dan notifikasi tampil dalam WIB | `config/app.php`, `.env.example` |
| 5.4 | Heartbeat queue & scheduler: timestamp di cache dari scheduler dan `Queue::after`; banner di layout jika mati | Banner muncul ≤ 3 menit setelah worker atau scheduler berhenti, dan hilang saat jalan lagi | `app/Providers/AppServiceProvider.php`, `routes/console.php`, `resources/views/layouts/app.blade.php` |
| 5.5 | Halaman Antrean: job pending per queue, daftar job gagal dengan tombol retry/hapus | Job gagal terlihat dan bisa di-retry dari UI tanpa terminal | controller & view baru |
| 5.6 | Kolom `created_by` (nullable, FK users) di leads, campaigns, outreach_messages, message_templates; diisi otomatis dari user login | Data baru tercatat pembuatnya; UI tidak berubah | migration baru, model terkait |
| 5.7 | Checklist onboarding di dashboard: AI tersambung, email tes berhasil, WhatsApp gateway tersambung (setelah Fase 6), scrape pertama, template pertama | Pengguna baru tahu langkah yang belum selesai | `DashboardController`, `dashboard.blade.php` |
| 5.8 | UI satu bahasa (Indonesia) | Tidak ada lagi label/judul Inggris di halaman utama | view di `resources/views/` |
| 5.9 | Uji deteksi balasan IMAP ke Gmail sungguhan dan perbaiki bila perlu | Balasan nyata di Gmail menandai pesan `replied` dalam ≤ 10 menit | `app/Services/Replies/` |
| 5.10 | Lupa password (opsional) | Reset password lewat email berjalan | auth controller & view |

## Fase 6: WhatsApp otomatis via gateway (±2 minggu)

**Kenapa:** temuan 1. Bagi 42% lead, WhatsApp adalah satu-satunya jalur; saat ini pengirimannya masih manual satu per satu.

| # | Yang dibuat | Selesai jika | File utama |
|---|---|---|---|
| 6.1 | Deteksi nomor seluler vs kantor (prefiks 08/628 vs kode area seperti 031, 021) | Lead ditandai "WA aktif" hanya untuk nomor seluler; tombol dan pengiriman WA disembunyikan untuk nomor kantor | `app/Helpers/Phone.php` |
| 6.2 | Interface `WhatsAppGateway` dengan driver **Fonnte** (default), **Wablas**, dan `manual` (click-to-chat seperti sekarang) | Ganti gateway cukup dari Pengaturan, tanpa ubah kode | `app/Services/WhatsApp/` (pola sama dengan `app/Services/Scraping/LeadSource.php`) |
| 6.3 | Pengaturan gateway di Pengaturan → Koneksi: provider, token (terenkripsi), nomor pengirim, tombol "Kirim WA tes" | Tes kirim ke nomor sendiri berhasil dari UI | `SettingsController`, `Setting`, `RuntimeConfig` |
| 6.4 | Pengiriman WA lewat antrean yang sama dengan email: `OutreachSender::queue()`/`dispatchDue()` per channel, `SendOutreachJob` memanggil gateway | Bulk "Kirim via antrean" berlaku untuk email dan WA | `app/Services/OutreachSender.php`, `app/Jobs/SendOutreachJob.php` |
| 6.5 | Batas ketat khusus WA: mis. 10/jam, 50/hari, jeda acak lebih panjang, hanya jam kerja | Batas bisa diubah di Pengaturan dan tidak pernah terlampaui | `config/leadhunter.php`, `OutreachSender` |
| 6.6 | Perlindungan nomor pengirim: cek blacklist, satu pesan per lead per campaign, pesan bervariasi (AI), peringatan untuk memakai nomor khusus bisnis dan "memanaskan" nomor baru | Tidak ada pesan duplikat ke lead yang sama; peringatan tampil sebelum kirim massal pertama | `OutreachSender`, view Outreach |
| 6.7 | Webhook pesan masuk (route publik dengan token): balasan menjadi `replied` + stage lead; "BERHENTI"/"STOP" masuk blacklist | Balasan WA tercatat otomatis (selama lokal butuh tunnel seperti Cloudflare Tunnel/ngrok) | route + controller baru, `ReplyDetector` |
| 6.8 | Status dari gateway (terkirim / gagal / nomor tidak terdaftar di WA) | Pesan gagal berstatus `failed` dengan alasan jelas di `last_error` | `SendOutreachJob`, `OutreachMessage::markFailed()` |

Catatan: WhatsApp Cloud API resmi (Meta) tidak cocok untuk cold outreach karena pesan pertama wajib memakai template yang disetujui Meta. Tetap bisa ditambahkan sebagai driver nanti. Gateway tidak resmi seperti Fonnte/Wablas punya risiko nomor diblokir; batas di 6.5 dan 6.6 adalah mitigasinya, bukan jaminan.

## Fase 7: Kualitas lead & personalisasi (±2 minggu)

**Kenapa:** temuan 1, 6, 7. Lebih banyak kontak yang valid dan urutan prioritas yang jelas membuat waktu outreach lebih efektif.

| # | Yang dibuat | Selesai jika | File utama |
|---|---|---|---|
| 7.1 | Skor lead 0–100: tanpa website / hanya Instagram, rating tinggi, banyak ulasan, punya kontak yang bisa dihubungi | Tabel Leads bisa diurutkan dan difilter berdasarkan skor; lead teratas diberi badge "Hot" | `Lead`, migration, `leads/index.blade.php` |
| 7.2 | Crawl massal (job) untuk lead yang punya website tapi belum ada email | Satu klik menjalankan crawl untuk semua lead terpilih di background | `WebsiteCrawlerService`, job baru |
| 7.3 | Audit website otomatis untuk lead yang sudah punya website (PageSpeed Insights API: skor mobile, HTTPS) | Hasil audit tersimpan dan dipakai sebagai fakta di sudut pesan | `AiService::angle()`, service baru |
| 7.4 | Dedup berbasis place id dari URL Google Maps, bukan nama + kota | Cabang dengan nama sama tersimpan sebagai lead terpisah; data lama dimigrasi | `LeadScraperService::saveLead()`, migration |
| 7.5 | Composer outreach memakai pencarian AJAX + pagination (ganti `Lead::all()`); "pilih semua hasil filter" di tabel Leads | Halaman Outreach tetap cepat di ≥ 5.000 lead | `OutreachController`, `CampaignController@filterLeads` (sudah ada), `outreach/index.blade.php` |
| 7.6 | Scraper mendeteksi CAPTCHA/consent Google dan melaporkannya dengan jelas | Notifikasi gagal menyebut penyebabnya, bukan hanya "0 hasil" | `scripts/scrape-gmaps.js`, `PuppeteerGoogleMapsSource` |

## Fase 8: Sequence multi-kanal & deliverability (±2 minggu)

**Kenapa:** temuan 9. Satu pesan + satu follow-up terlalu sedikit, dan reply rate saat ini tercampur auto-reply.

| # | Yang dibuat | Selesai jika | File utama |
|---|---|---|---|
| 8.1 | Sequence multi-langkah per campaign, lintas kanal (mis. email hari 0 → WA hari +3 → email penutup hari +7); berhenti otomatis saat reply/unsubscribe di kanal mana pun | Langkah bisa diatur per campaign dan tidak ada pesan terkirim setelah lead membalas | generalisasi `FollowupService`, tabel langkah baru |
| 8.2 | Jendela kirim (hari kerja, 08–16 WIB) + batas harian untuk semua kanal | Tidak ada pesan terkirim di luar jendela | `OutreachSender::dispatchDue()` |
| 8.3 | Deteksi bounce (mailer-daemon) | Email bounce menjadi `failed` dan alamatnya masuk blacklist dengan alasan "bounce" | `ReplyDetector` |
| 8.4 | Klasifikasi balasan dengan AI: tertarik / tanya harga / tidak tertarik / auto-reply | Stage lead berubah otomatis; auto-reply tidak dihitung sebagai reply | `ReplyDetector`, `AiService` |
| 8.5 | Analitik per langkah sequence dan per subjek | Dashboard menunjukkan langkah dan subjek mana yang paling banyak dibalas | `DashboardController::abStats()` |

## Fase 9: AI terukur & andal (±1 minggu)

**Kenapa:** temuan 2.

| # | Yang dibuat | Selesai jika | File utama |
|---|---|---|---|
| 9.1 | Log pemakaian AI: fitur, model, latensi, token (dari `usage`), error | Ringkasan harian (jumlah panggilan, rata-rata detik, token) tampil di Pengaturan | `AiService::generateText()`, tabel log baru |
| 9.2 | Provider cadangan otomatis saat timeout/error, dan model per fitur (cepat untuk matching, kualitas untuk pesan) | Saat router utama mati, generate tetap jalan lewat provider kedua | `Setting`, `RuntimeConfig`, `AiService` |
| 9.3 | Quality gate output: panjang, frasa terlarang, placeholder tersisa, angka rating/alamat | Output yang melanggar di-regenerate sekali, lalu ditandai "perlu review" | `AiService::parseOutreach()` |
| 9.4 | Varian gaya prompt diukur dengan reply rate | Dashboard membandingkan reply rate antar varian | `AiService`, `DashboardController` |

## Fase 10: Tim & deploy (saat dibutuhkan)

| # | Yang dibuat | Selesai jika |
|---|---|---|
| 10.1 | Mode tim memakai `created_by` dari Fase 5: filter "milik saya", assign lead ke anggota, batas kirim per pengirim. Tetap satu organisasi, bukan multi-tenant | Dua user bisa bekerja tanpa saling menimpa lead |
| 10.2 | Laporan mingguan via email ke admin | Email ringkasan terkirim setiap Senin pagi |
| 10.3 | Deploy production sesuai [docs/deployment.md](docs/deployment.md) + backup otomatis harian + monitoring uptime | Webhook WhatsApp dan link unsubscribe berjalan tanpa tunnel |

---

## Metrik sukses

| Metrik | Sekarang | Target |
|---|---|---|
| Lead yang punya email | 36% | ≥ 50% (crawl massal, 7.2) |
| Lead yang bisa dihubungi (email atau WA seluler) | 78% | ≥ 85% |
| Lead "hanya WA" yang benar-benar dihubungi | 0% (manual) | ≥ 80% lewat gateway |
| Gagal kirim WhatsApp | – | < 5%, nol pemblokiran nomor di bulan pertama |
| Job gagal yang tidak terlihat | tidak terpantau | 0; banner ≤ 3 menit setelah worker mati |
| Reply rate | tercampur auto-reply | terukur per kanal, per langkah sequence, dan per varian prompt |

## Tetap tidak dikerjakan

Microservices, RabbitMQ, websocket, LinkedIn/Instagram automation, multi-tenant SaaS, AI memory, dan RAG/vector database, sesuai non-goals di [agents.md](agents.md).
