# CATETIN BOT — Setup Guide

## Tech Stack
- **Laravel 11**, PHP 8.3+
- **MySQL** (via phpMyAdmin di cPanel / lokal)
- **Telegram Bot API** (webhook di produksi, polling di dev)
- **Google Gemini API** — baca struk, transkripsi audio, parse intent
- **Livewire 3** + Tailwind CSS (CDN) + Chart.js (CDN)
- **Cache driver: file** (tidak butuh Redis, kompatibel cPanel shared hosting)
- **Timezone: Asia/Makassar (WITA)**

---

## PERSIAPAN CREDENTIALS

### 1. Telegram Bot Token
1. Buka Telegram → cari **@BotFather**
2. Ketik `/newbot` → ikuti panduan → catat **token** yang diberikan

### 2. Telegram Webhook Secret
Generate string acak minimal 32 karakter (wajib untuk keamanan webhook):
```bash
openssl rand -hex 32
```

### 3. Owner Chat ID (ID Telegram kamu)
1. Cari bot **@userinfobot** di Telegram → ketik `/start`
2. Catat angka **Id** yang ditampilkan

### 4. Gemini API Key
1. Buka [Google AI Studio](https://aistudio.google.com/apikey)
2. Klik **Create API Key** → pilih project → copy key

---

## SETUP LOKAL (Development)

### Langkah 1 — Buat database MySQL
```sql
-- Via terminal:
mysql -u root -p -e "CREATE DATABASE catetin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

-- Atau via phpMyAdmin:
-- New → Nama: catetin → Collation: utf8mb4_unicode_ci → Create
```

### Langkah 2 — Konfigurasi .env
```bash
cp .env.example .env
```

Edit `.env`, isi bagian ini minimal:
```env
APP_NAME=Catetin
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Makassar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=catetin
DB_USERNAME=root
DB_PASSWORD=          # sesuaikan

TELEGRAM_BOT_TOKEN=   # dari BotFather
TELEGRAM_WEBHOOK_SECRET=  # openssl rand -hex 32

GEMINI_API_KEY=       # dari Google AI Studio
GEMINI_MODEL=gemini-2.5-flash

OWNER_CHAT_ID=        # ID Telegram kamu
DASHBOARD_PASSWORD=   # password akses dashboard web
REMINDER_LEAD_MINUTES=20
```

### Langkah 3 — Install & migrate
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
```

### Langkah 4 — Jalankan

Buka **2 terminal terpisah**:

**Terminal 1 — Dashboard web:**
```bash
php artisan serve
# Dashboard: http://localhost:8000/dashboard
```

**Terminal 2 — Telegram bot (polling):**
```bash
php artisan telegram:poll
# Bot akan aktif, Ctrl+C untuk stop
# Sekaligus dispatch reminders tiap iterasi
```

### Langkah 5 — Set bot commands (sekali saja)
```bash
php artisan telegram:set-commands
```

---

## SETUP CPANEL (Production)

### A. Persiapan Hosting

Pastikan cPanel mendukung:
- PHP 8.2+ (set via cPanel → PHP Selector)
- MySQL 5.7+ / MariaDB 10.3+
- HTTPS (SSL aktif — wajib untuk webhook Telegram)

### B. Upload Project

**Opsi 1 — SSH (disarankan):**
```bash
# Upload via scp atau git clone ke /home/USER/
scp -r ./catetinbot user@server:/home/USER/catetinbot

# Atau git clone langsung di server
cd /home/USER && git clone https://github.com/kamu/catetinbot.git
```

**Opsi 2 — File Manager cPanel:**
- Zip seluruh project → upload ke `/home/USER/` → ekstrak
- Pastikan folder `catetinbot/` ada di dalam `/home/USER/`

### C. Document Root

Di cPanel → **Domains** → domain/subdomain → Edit → ubah Document Root ke:
```
/home/USER/catetinbot/public
```

Atau buat symlink di `public_html`:
```bash
# Jika domain root = public_html
ln -s /home/USER/catetinbot/public /home/USER/public_html
```

### D. Install Dependencies (via SSH)
```bash
cd /home/USER/catetinbot
php artisan --version   # pastikan PHP 8.2+
composer install --no-dev --optimize-autoloader
```

### E. Konfigurasi .env (Production)
```bash
cp .env.example .env
nano .env   # atau edit via File Manager cPanel
```

Isi `.env` untuk production:
```env
APP_NAME=Catetin
APP_ENV=production
APP_DEBUG=false                          # WAJIB false di produksi
APP_URL=https://domain-kamu.com
APP_TIMEZONE=Asia/Makassar
APP_KEY=                                 # generate di langkah F

DB_CONNECTION=mysql
DB_HOST=localhost                        # biasanya localhost di cPanel
DB_PORT=3306
DB_DATABASE=USER_catetin                 # format: namauser_namadatabase
DB_USERNAME=USER_dbuser
DB_PASSWORD=passworddb

CACHE_STORE=file
SESSION_DRIVER=file

TELEGRAM_BOT_TOKEN=xxx
TELEGRAM_WEBHOOK_SECRET=xxx
GEMINI_API_KEY=xxx
GEMINI_MODEL=gemini-2.5-flash
OWNER_CHAT_ID=xxx
DASHBOARD_PASSWORD=xxx
REMINDER_LEAD_MINUTES=20
```

### F. Setup Aplikasi
```bash
cd /home/USER/catetinbot

# Generate app key
php artisan key:generate

# Buat database MySQL dulu via phpMyAdmin cPanel, lalu:
php artisan migrate --force

# Storage link untuk akses foto struk via web
php artisan storage:link

# Optimasi cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### G. Permission File
```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

### H. Buat Database di cPanel
1. cPanel → **MySQL Databases**
2. Buat database baru: `catetin` (akan jadi `USER_catetin`)
3. Buat user database baru dengan password kuat
4. Tambahkan user ke database → berikan **All Privileges**
5. Update `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env`

### I. Set Webhook Telegram
```bash
php artisan telegram:set-webhook https://domain-kamu.com/api/telegram/webhook
# Output: ✅ Webhook berhasil diset!

php artisan telegram:set-commands
# Output: ✅ Bot commands berhasil diset!
```

Verifikasi webhook aktif:
```
https://api.telegram.org/bot{TOKEN}/getWebhookInfo
```

### J. Cron Job cPanel

cPanel → **Cron Jobs** → tambahkan 1 baris:
```
* * * * * cd /home/USER/catetinbot && php artisan schedule:run >> /dev/null 2>&1
```

> **Ganti `USER`** dengan username cPanel kamu.

Ini menjalankan semua scheduler Laravel:
- **Setiap menit** — kirim reminders yang jatuh waktu
- **07:00 WITA** — auto-charge langganan + reminder sebelum jatuh tempo
- **08:00 WITA** — reminder utang/piutang yang jatuh tempo besok
- **Minggu 20:00 WITA** — rekap mingguan ke Telegram
- **Akhir bulan 20:00 WITA** — rekap bulanan ke Telegram

---

## VERIFIKASI SETUP

### Cek koneksi database
```bash
php artisan db:show
```

### Test bot (tes manual)
1. Buka bot di Telegram → kirim `/start`
2. Kirim `makan siang 25000`
3. Cek respons bot (harus balas dengan detail transaksi + status budget)

### Cek dashboard
Buka `https://domain-kamu.com/dashboard` → masukkan `DASHBOARD_PASSWORD`

### Cek webhook aktif
```bash
curl https://api.telegram.org/bot{TOKEN}/getWebhookInfo
```
Response harus ada `"url": "https://domain-kamu.com/api/telegram/webhook"` dan `"pending_update_count": 0`

### Cek scheduler jalan
```bash
php artisan schedule:list
```

---

## TROUBLESHOOTING

| Error | Kemungkinan Penyebab | Solusi |
|-------|---------------------|--------|
| `Unknown database 'catetin'` | Database belum dibuat | Buat via phpMyAdmin dulu |
| `SQLSTATE[HY000] [1045]` | Username/password salah | Cek `.env` DB_USERNAME & DB_PASSWORD |
| Webhook tidak merespons | Domain belum HTTPS | Pastikan SSL aktif di cPanel |
| Bot tidak balas | Token salah / webhook gagal | Cek `getWebhookInfo`, re-run `set-webhook` |
| Struk tidak terbaca | Gemini API gagal | Cek `GEMINI_API_KEY` valid & quota cukup |
| Dashboard 403/redirect loop | `DASHBOARD_PASSWORD` kosong | Set password di `.env` |
| Foto struk tidak muncul | `storage:link` belum dijalankan | `php artisan storage:link` |
| Scheduler tidak jalan | Cron Job salah path | Verifikasi path di cPanel Cron Jobs |
| `APP_KEY` error | Key belum digenerate | `php artisan key:generate` |

---

## STRUKTUR FOLDER

```
catetinbot/
├── app/
│   ├── Console/Commands/
│   │   ├── DebtsRemind.php          ← reminder utang/piutang jatuh tempo
│   │   ├── RecapSend.php            ← kirim rekap ke Telegram
│   │   ├── RemindersDispatch.php    ← dispatch reminder aktif
│   │   ├── SubscriptionsRun.php     ← auto-charge langganan
│   │   ├── TelegramPoll.php         ← polling dev mode
│   │   ├── TelegramSetCommands.php  ← set menu bot
│   │   └── TelegramSetWebhook.php   ← set webhook URL
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   └── TelegramWebhookController.php  ← handler utama bot
│   │   └── Middleware/
│   │       └── DashboardPassword.php
│   ├── Livewire/                    ← 9 komponen dashboard
│   ├── Repositories/                ← 8 repository (raw SQL)
│   ├── Services/
│   │   ├── TelegramService.php
│   │   ├── GeminiService.php
│   │   └── FinanceService.php
│   └── Support/
│       ├── JsonExtractor.php        ← parse JSON dari Gemini
│       └── helpers.php              ← rp(), today_wita(), week_start(), dll
├── database/migrations/             ← 11 migration
├── resources/views/
│   ├── dashboard/login.blade.php
│   ├── layouts/dashboard.blade.php
│   └── livewire/                    ← 9 blade views
├── routes/
│   ├── api.php                      ← POST /api/telegram/webhook
│   ├── console.php                  ← semua schedule
│   └── web.php                      ← dashboard routes
├── storage/app/public/receipts/     ← foto struk disimpan di sini
├── SETUP.md                         ← panduan ini
└── TESTING.md                       ← checklist testing
```

---

## KEAMANAN PRODUKSI

- `APP_DEBUG=false` — wajib, mencegah stack trace tampil ke publik
- `APP_ENV=production` — aktifkan mode produksi Laravel
- `TELEGRAM_WEBHOOK_SECRET` — validasi setiap request webhook
- `DASHBOARD_PASSWORD` — proteksi akses dashboard web
- Rate limit webhook: 30 request/menit per IP (via Laravel RateLimiter)
- Semua query pakai parameter binding `?` — zero SQL injection
- CSRF aktif otomatis di semua form Livewire
- Output dashboard auto-escaped oleh Livewire/Blade

---

## UPDATE / DEPLOY ULANG

```bash
cd /home/USER/catetinbot

git pull origin main                    # atau upload file baru

composer install --no-dev --optimize-autoloader

php artisan migrate --force             # jika ada migration baru

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jika ada perubahan storage:
php artisan storage:link
```
