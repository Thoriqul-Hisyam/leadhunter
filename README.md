# LeadHunter AI (Sandesa)

Aplikasi untuk freelancer, agency, dan digital marketer yang mencari calon klien secara otomatis: scraping bisnis dari Google Maps, mencari email/WhatsApp dari website mereka, membuat pesan outreach personal dengan AI, lalu mengirim dan melacak hasilnya.

Dibuat dengan pendekatan MVP: sederhana, cepat, langsung bisa dipakai.

## Fitur

**Lead**
- Scraping bisnis dari Google Maps berdasarkan niche + kota (nama, alamat, telepon, website, rating, jumlah ulasan, kategori, link Maps)
- Crawl website bisnis untuk mencari email dan nomor WhatsApp
- Filter lead berdasarkan ketersediaan email, WhatsApp, website, dan stage
- Pipeline lead: Baru → Dihubungi → Membalas → Meeting → Deal / Batal, plus catatan per lead
- Import dan export CSV

**Outreach**
- Pesan email & WhatsApp dengan AI, template, atau template yang dipoles AI (hybrid)
- Generate pesan untuk banyak lead sekaligus di background, dengan progress bar
- Message Template yang bisa ditulis AI, dengan placeholder seperti `{{business_name}}`
- Kirim email langsung, atau lewat antrean dengan batas per jam dan jeda acak (aman untuk Gmail)
- WhatsApp click-to-chat (`wa.me`)
- Follow-up otomatis untuk lead yang belum membalas
- Link unsubscribe dan blacklist
- Deteksi balasan otomatis lewat IMAP Gmail (opsional)

**Lainnya**
- Dashboard: sent rate, reply rate, failed rate, grafik 30 hari, performa per campaign, dan perbandingan reply rate per template & mode
- Role & permission (admin / user)
- Mode terang dan gelap

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
| `queue:listen --queue=default` | generate pesan AI, kirim email |
| `queue:listen --queue=scraping` | scraping Google Maps (bisa sampai 15 menit) |
| `schedule:work` | antrean kirim email, retry, follow-up, cek balasan, cleanup |
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
- **Follow-up otomatis:** aktif/nonaktif dan jeda harinya.
- **Koneksi AI:** Base URL, API key, dan model. Tekan **Tes AI** setelah menyimpan.
- **Email (SMTP):** mode kirim (SMTP atau log), host, port, username Gmail, App Password, alamat dan nama pengirim, serta toggle deteksi balasan (IMAP). Tekan **Kirim email tes** setelah menyimpan.
- **Blacklist:** email atau nomor yang tidak boleh dihubungi.

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
| `OUTREACH_HOURLY_LIMIT` | `20` | batas email per jam dari antrean |
| `OUTREACH_MIN_GAP_SECONDS` / `OUTREACH_MAX_GAP_SECONDS` | `90` / `240` | jeda acak antar email |
| `AI_TIMEOUT` / `AI_RETRIES` | `120` / `2` | batas waktu dan retry panggilan AI |
| `ADMIN_PASSWORD` | `admin123` | password admin saat `db:seed` |
| `MAIL_MAILER` | `log` | hanya dipakai jika mode kirim belum dipilih di Pengaturan |

## Alur pemakaian

1. **Pengaturan:** isi identitas pengirim, koneksi AI, dan email.
2. **Leads:** masukkan niche + kota (misalnya "klinik gigi" + "Surabaya") lalu klik *Scrape Leads*. Proses berjalan di background; notifikasi muncul di ikon lonceng saat selesai.
3. **Campaign:** buat campaign, pilih lead (manual atau *Smart Select*), pilih mode AI atau template, dan centang *auto generate*. Pesan AI dibuat di background dengan progress bar.
4. **Review:** buka detail campaign atau halaman Outreach, lalu edit atau generate ulang pesan jika perlu.
5. **Kirim:** email bisa dikirim satu per satu, atau pilih beberapa lalu gunakan aksi massal *Kirim Email via Antrean*. WhatsApp membuka `wa.me` dengan pesan terisi.
6. **Lacak:** status berubah otomatis (sent, replied jika IMAP aktif). Atur stage di halaman Pipeline dan pantau angka di Dashboard.

## Perintah artisan

| Perintah | Jadwal | Fungsi |
|---|---|---|
| `outreach:send-due` | tiap menit | kirim email antrean yang jatuh tempo, mematuhi batas per jam |
| `outreach:retry-failed` | tiap 15 menit | jadwalkan ulang email yang gagal karena error sementara |
| `outreach:followups` | tiap jam | buat pesan follow-up (status pending, untuk direview) |
| `outreach:check-replies` | tiap 10 menit | baca inbox via IMAP, tandai replied / masukkan permintaan berhenti ke blacklist |
| `leadhunter:cleanup` | harian | hapus folder profil Chrome sisa scraping dan notifikasi lama |

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
├── Helpers/            Url (normalisasi + proteksi SSRF), Phone (format WhatsApp)
├── Http/Controllers/
├── Jobs/               ScrapeGoogleMapsJob, GenerateOutreachJob, SendOutreachJob
├── Mail/
├── Models/             Setting menyimpan pengaturan (secret terenkripsi)
└── Services/
    ├── AiService.php           semua prompt & panggilan AI
    ├── OutreachGenerator.php   buat pesan untuk banyak lead
    ├── OutreachSender.php      kirim email & antrean
    ├── RuntimeConfig.php       terapkan pengaturan dari database ke config
    ├── Replies/                deteksi balasan (IMAP)
    └── Scraping/               driver Puppeteer / Google Places / Apify
config/leadhunter.php   konfigurasi scraper, pengiriman, IMAP
scripts/                script Node (Puppeteer)
docs/                   dokumentasi deploy
```

Panduan untuk developer dan AI agent (konvensi kode, skema lengkap) ada di [agents.md](agents.md).

## Troubleshooting

| Masalah | Penyebab & solusi |
|---|---|
| Scraping tidak pernah selesai | Queue worker `scraping` tidak berjalan. Jalankan `composer run dev`. |
| Scraping berhenti setelah ±1 menit | Worker dijalankan tanpa `--timeout=0`. Pakai `composer run dev` atau tambahkan opsi itu. |
| Scraping gagal "Tidak ada hasil" | Google Maps menampilkan CAPTCHA atau kata kunci terlalu spesifik. Coba kata kunci lebih umum, atau gunakan `SCRAPER_DRIVER=google_places`. |
| Error "AI belum dikonfigurasi" | Isi Koneksi AI di halaman Pengaturan, lalu tekan *Tes AI*. |
| Email berstatus sent tapi tidak sampai | Mode kirim masih "log". Pilih SMTP di Pengaturan dan tekan *Kirim email tes*. |
| Gmail menolak login SMTP | Gunakan App Password (bukan password akun) dan pastikan verifikasi 2 langkah aktif. |
| API key/password tiba-tiba kosong | `APP_KEY` berubah. Isi ulang di halaman Pengaturan. |
| Pesan AI lama sekali | Kecepatan tergantung penyedia AI. Generate campaign berjalan di background, jadi halaman tidak perlu ditunggu. |
| `composer install` gagal soal versi PHP | PHP di terminal masih versi lama. Ganti ke 8.3 (lihat bagian Kebutuhan). |

## Catatan

Scraping DOM Google Maps rapuh terhadap perubahan tampilan dan melanggar Ketentuan Layanan Google. Untuk pemakaian serius, pertimbangkan driver `google_places` (API resmi) atau `apify`. Kirim cold email secukupnya, hormati permintaan berhenti, dan jangan melewati batas kirim harian penyedia email Anda.
