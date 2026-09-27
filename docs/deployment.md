# Deploy ke VPS Linux

Panduan untuk Ubuntu 22.04/24.04 dengan Nginx + PHP-FPM 8.3 + MySQL 8. Ganti `leadhunter.example.com` dan `/var/www/leadhunter` sesuai server Anda.

## 1. Paket sistem

```bash
sudo apt update
sudo apt install -y nginx mysql-server supervisor unzip git \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Node 22 (dibutuhkan Vite 8 & Puppeteer 25)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs

# Chrome headless untuk scraper Puppeteer (lewati jika SCRAPER_DRIVER=google_places/apify)
wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt install -y ./google-chrome-stable_current_amd64.deb
```

## 2. Kode & dependency

```bash
sudo mkdir -p /var/www/leadhunter && sudo chown $USER:www-data /var/www/leadhunter
git clone <repo-url> /var/www/leadhunter && cd /var/www/leadhunter

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

## 3. `.env` production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://leadhunter.example.com   # wajib publik & https: dipakai link unsubscribe di email

DB_DATABASE=leadhunter
DB_USERNAME=leadhunter
DB_PASSWORD=<password kuat>

QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=1000

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=emailanda@gmail.com
MAIL_PASSWORD=<App Password Gmail>
MAIL_FROM_ADDRESS=emailanda@gmail.com

AI_BASE_URL=...
AI_API_KEY=...
AI_MODEL=...

CHROME_PATH=/usr/bin/google-chrome
ADMIN_PASSWORD=<password admin baru>

# opsional
IMAP_ENABLED=true
```

Lalu:

```bash
php artisan migrate --force
php artisan db:seed --force          # membuat role, permission, admin (password dari ADMIN_PASSWORD)
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

Jika admin sudah ada sebelum `ADMIN_PASSWORD` diisi, ganti passwordnya lewat menu **Kelola Pengguna** atau:

```bash
php artisan tinker --execute='App\Models\User::where("email","admin@leadhunter.com")->first()->update(["password" => bcrypt("password-baru")]);'
```

## 4. Nginx

`/etc/nginx/sites-available/leadhunter`:

```nginx
server {
    listen 80;
    server_name leadhunter.example.com;
    root /var/www/leadhunter/public;
    index index.php;

    client_max_body_size 10M;   # import CSV

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 300;   # preview composer AI bisa ~1-2 menit
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/leadhunter /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d leadhunter.example.com
```

Naikkan juga `max_execution_time = 300` di `/etc/php/8.3/fpm/php.ini`, lalu `sudo systemctl restart php8.3-fpm`.

## 5. Queue worker (Supervisor)

Dua worker: `default` (generate AI, kirim email) dan `scraping` (job panjang, sampai 15 menit).

`/etc/supervisor/conf.d/leadhunter.conf`:

```ini
[program:leadhunter-queue]
command=php /var/www/leadhunter/artisan queue:work --queue=default --sleep=3 --tries=1 --timeout=300 --max-time=3600
directory=/var/www/leadhunter
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=320
redirect_stderr=true
stdout_logfile=/var/www/leadhunter/storage/logs/queue.log

[program:leadhunter-scraper]
command=php /var/www/leadhunter/artisan queue:work --queue=scraping --sleep=5 --tries=1 --timeout=900 --max-time=7200
directory=/var/www/leadhunter
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=920
redirect_stderr=true
stdout_logfile=/var/www/leadhunter/storage/logs/scraper.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status
```

Setelah setiap deploy kode baru: `php artisan queue:restart`.

## 6. Scheduler (cron)

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/leadhunter && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan: kirim antrean email (tiap menit), retry email gagal (15 menit), follow-up (tiap jam), cek reply IMAP (10 menit), dan cleanup profil Chrome (harian).

## 7. Checklist keamanan

- [ ] `APP_DEBUG=false`
- [ ] Password admin default sudah diganti
- [ ] HTTPS aktif (certbot) dan `APP_URL` memakai `https://`
- [ ] User MySQL khusus aplikasi, bukan `root`
- [ ] `composer audit` bersih (jalankan `composer update` untuk paket yang punya advisory, lalu `php artisan test`)
- [ ] Backup database terjadwal, misalnya `mysqldump` harian via cron

## Catatan scraping

Scraping DOM Google Maps rapuh (selector bisa berubah) dan melanggar ToS Google, apalagi dari IP server yang mudah terkena CAPTCHA. Untuk production, pertimbangkan `SCRAPER_DRIVER=google_places` (resmi, berbayar setelah kuota gratis) atau `SCRAPER_DRIVER=apify`.
