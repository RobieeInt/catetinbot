# CATETIN BOT — Testing Checklist

Jalankan checklist ini setelah setup selesai. Centang tiap item yang sudah diverifikasi.

**Mode test:** `php artisan telegram:poll` (dev) atau webhook aktif (produksi)

---

## 1. SETUP & KONEKSI

- [ ] `php artisan migrate` berhasil tanpa error (11 tabel terbuat)
- [ ] `php artisan storage:link` berhasil
- [ ] `php artisan telegram:set-commands` berhasil
- [ ] `php artisan serve` jalan, `/dashboard/login` muncul
- [ ] Login dashboard dengan `DASHBOARD_PASSWORD` berhasil
- [ ] `/start` di Telegram → bot membalas dengan panduan penggunaan
- [ ] Dompet "Cash" otomatis terbuat saat user pertama interaksi

---

## 2. CATAT TRANSAKSI — TEKS MANUAL

### 2.1 Expense biasa
- [ ] Kirim: `makan siang 25000`
  - Ekspektasi: konfirmasi expense Rp25.000, kategori "Makanan & Minuman"
  - Status budget tampil jika budget sudah diset

### 2.2 Expense dengan dompet
- [ ] Kirim: `bayar grabfood 35rb pake gopay`
  - Ekspektasi: wallet "gopay" otomatis dibuat (jika belum ada), saldo gopay tampil

### 2.3 Income
- [ ] Kirim: `gajian 5 juta`
  - Ekspektasi: tipe "income", nominal Rp5.000.000

### 2.4 Expense dengan nominal ribuan
- [ ] Kirim: `parkir 3rb`
  - Ekspektasi: Rp3.000

### 2.5 Nominal juta
- [ ] Kirim: `beli laptop 8.5jt`
  - Ekspektasi: Rp8.500.000

### 2.6 Validasi nominal invalid
- [ ] Kirim: `makan nol rupiah`
  - Ekspektasi: pesan error "❌ Nominal tidak valid"

---

## 3. CATAT TRANSAKSI — VOICE NOTE

- [ ] Rekam voice note: "beli bensin dua puluh ribu"
  - Ekspektasi: transkripsi ditampilkan, lalu diproses sebagai transaksi expense Rp20.000
- [ ] Rekam voice note: "gajian bulan ini lima juta"
  - Ekspektasi: income Rp5.000.000

---

## 4. CATAT TRANSAKSI — FOTO STRUK

### 4.1 Confidence tinggi (≥ 0.60)
- [ ] Kirim foto struk/nota yang jelas
  - Ekspektasi: langsung simpan, tampilkan detail merchant + items + total + status budget

### 4.2 Confidence rendah (< 0.60)
- [ ] Kirim foto buram atau setengah terpotong
  - Ekspektasi: bot tanya konfirmasi "⚠️ Saya kurang yakin..." dengan total + kategori
  - Balas "YA" → transaksi tersimpan
  - Balas "TIDAK" → dibatalkan "❌ Dibatalkan."

### 4.3 Foto bukan struk
- [ ] Kirim foto selfie / foto random
  - Ekspektasi: "❌ Gambar ini bukan struk/nota"

### 4.4 Struk tersimpan di storage
- [ ] Setelah struk berhasil diproses, cek di dashboard → Transaksi → tombol "Struk" muncul
- [ ] Klik "Struk" → foto tampil di modal
- [ ] Klik "Download Struk" → file terunduh

---

## 5. TRANSFER ANTAR DOMPET

- [ ] Buat minimal 2 dompet dulu (misal BCA dan GoPay via chat atau dashboard)
- [ ] Kirim: `transfer 500rb dari BCA ke GoPay`
  - Ekspektasi: konfirmasi transfer, saldo kedua dompet update
  - Transfer TIDAK masuk expense/income
- [ ] Kirim: `transfer 1jt dari BCA ke GoPay` saat saldo BCA < 1jt
  - Ekspektasi: error "❌ Saldo tidak cukup"
- [ ] Dashboard → Dompet → riwayat transfer tampil

---

## 6. BUDGET

### 6.1 Set budget
- [ ] Kirim: `budget harian 100000`
  - Ekspektasi: "✅ Budget harian diset ke Rp100.000"
- [ ] Kirim: `budget mingguan 500000`
- [ ] Kirim: `budget bulanan 3000000`
- [ ] Dashboard → Pengaturan → nilai budget tampil, bisa edit + save

### 6.2 Status budget saat catat
- [ ] Catat expense saat sudah ada budget
  - Ekspektasi: setelah konfirmasi transaksi, tampil baris budget (hari/minggu/bulan)
  - Icon ✅ jika < 80%, ⚠️ jika 80–99%, 🚨 jika ≥ 100%

### 6.3 Cek sisa budget
- [ ] Kirim: `sisa` → rekap sisa budget mingguan
- [ ] Kirim: `sisa harian` → sisa budget hari ini
- [ ] Kirim: `sisa bulanan` → sisa budget bulan ini

---

## 7. REKAP

- [ ] Kirim: `rekap`
  - Ekspektasi: expense vs income minggu ini, net, breakdown kategori, top 3 item, perbandingan vs minggu lalu
- [ ] Kirim: `rekap bulanan`
  - Ekspektasi: rekap bulan ini vs bulan lalu
- [ ] Kirim: `rekap harian`

---

## 8. SALDO

- [ ] Kirim: `saldo`
  - Ekspektasi: semua dompet + total
- [ ] Kirim: `saldo gopay`
  - Ekspektasi: hanya saldo GoPay
- [ ] Kirim: `saldo dompet yang tidak ada`
  - Ekspektasi: "❌ Dompet tidak ditemukan"

---

## 9. UNDO & HAPUS

### 9.1 Undo (dalam 5 menit)
- [ ] Catat transaksi → langsung kirim `undo`
  - Ekspektasi: "↩️ Transaksi terakhir dibatalkan."
  - Cek di dashboard → transaksi hilang

### 9.2 Undo (setelah 5 menit)
- [ ] Catat transaksi → tunggu > 5 menit → kirim `undo`
  - Ekspektasi: "⚠️ Batas waktu undo sudah habis (maks 5 menit)."

### 9.3 Undo tanpa transaksi
- [ ] Kirim `undo` tanpa riwayat transaksi
  - Ekspektasi: "❌ Tidak ada transaksi untuk di-undo."

### 9.4 Hapus via chat
- [ ] Kirim: `hapus` → transaksi terakhir dihapus permanen (tanpa batas waktu)

### 9.5 Edit via chat
- [ ] Catat transaksi → kirim: `koreksi tadi jadi 30000 kategori transport`
  - Ekspektasi: transaksi terakhir diupdate

---

## 10. PENGINGAT (REMINDER)

### 10.1 Reminder sekali
- [ ] Kirim: `ingetin bayar listrik besok jam 9 pagi`
  - Ekspektasi: konfirmasi dengan waktu exak
- [ ] Tunggu sampai waktu reminder → bot kirim "⏰ Pengingat: bayar listrik"
- [ ] Setelah dikirim, reminder tidak muncul lagi (notified=1)

### 10.2 Reminder harian
- [ ] Kirim: `ingetin minum vitamin tiap jam 8 pagi`
  - Ekspektasi: repeat=daily
- [ ] Setelah dikirim, `remind_at` bergeser +1 hari (cek di DB atau dashboard)

### 10.3 List reminder
- [ ] Kirim: `lihat pengingat`
  - Ekspektasi: daftar reminder aktif

### 10.4 Dashboard reminder
- [ ] Dashboard → Pengingat → list aktif tampil, bisa tambah/nonaktif/hapus

---

## 11. LANGGANAN (SUBSCRIPTION)

### 11.1 Tambah via chat
- [ ] Kirim: `langganan Netflix 54000 tiap tanggal 5`
  - Ekspektasi: konfirmasi, muncul di dashboard

### 11.2 Auto-charge
- [ ] Jalankan manual: `php artisan subscriptions:run`
  - Jika hari ini = tanggal tagih: transaksi expense otomatis dibuat, bot kirim notif
  - `last_charged_month` terupdate ke bulan ini

### 11.3 Reminder sebelum jatuh tempo
- [ ] Simulasikan hari = tanggal tagih - reminder_days_before (default 2)
  - `php artisan subscriptions:run` → bot kirim "⚠️ Langganan Netflix jatuh tempo tgl 5..."

### 11.4 Dashboard
- [ ] Dashboard → Langganan → list tampil, aktif/nonaktif, hapus

---

## 12. UTANG & PIUTANG

### 12.1 Catat utang
- [ ] Kirim: `utang ke Budi 200000`
  - Ekspektasi: "💳 Utang dicatat! Orang: Budi, Nominal: Rp200.000"

### 12.2 Catat piutang
- [ ] Kirim: `Anton ngutang ke aku 150rb`
  - Ekspektasi: tipe piutang

### 12.3 Lunas
- [ ] Kirim: `Budi lunas`
  - Ekspektasi: "✅ Utang Budi lunas! Nominal: Rp200.000"

### 12.4 Reminder jatuh tempo
- [ ] Catat utang dengan due date besok
- [ ] Jalankan: `php artisan debts:remind`
  - Ekspektasi: bot kirim notif "⏰ Utang ke Budi jatuh tempo besok!"

### 12.5 Dashboard
- [ ] Dashboard → Utang & Piutang → tab Utang / Piutang, tandai lunas, hapus

---

## 13. TABUNGAN (SAVINGS)

### 13.1 Buat goal
- [ ] Kirim: `nabung laptop 500000`
  - Ekspektasi: jika goal "laptop" belum ada, auto-buat goal + setoran pertama

### 13.2 Tambah setoran
- [ ] Kirim: `nabung laptop 200000`
  - Ekspektasi: progress diupdate, persentase tampil

### 13.3 Dashboard
- [ ] Dashboard → Tabungan → progress bar per goal, tambah goal, setor, hapus

---

## 14. DASHBOARD — OVERVIEW

- [ ] Kartu Hari Ini / Minggu Ini / Bulan Ini tampil dengan nominal benar
- [ ] Progress bar budget muncul jika budget sudah diset
- [ ] Icon ✅/⚠️/🚨 sesuai dengan persentase pemakaian
- [ ] Chart "Tren 30 Hari" render dengan data yang benar (bar chart)
- [ ] Chart "6 Bulan Terakhir" render dengan benar
- [ ] Breakdown kategori minggu ini tampil
- [ ] Saldo dompet tampil (negatif = merah)
- [ ] Pengingat aktif + utang jatuh tempo tampil di sidebar kanan

---

## 15. DASHBOARD — TRANSAKSI

- [ ] Tabel menampilkan transaksi dengan tanggal, keterangan, kategori, dompet, total
- [ ] Warna merah untuk expense, hijau untuk income
- [ ] Filter search (ketik nama merchant/catatan)
- [ ] Filter tipe (expense/income)
- [ ] Filter kategori
- [ ] Filter dompet
- [ ] Filter rentang tanggal (date_from + date_to)
- [ ] Kombinasi multiple filter sekaligus
- [ ] Pagination berfungsi (prev/next)
- [ ] Edit modal: ubah total + kategori + catatan → tersimpan, cache flush
- [ ] Hapus transaksi → confirm dialog → hilang dari tabel, cache flush
- [ ] Tombol "Struk" hanya muncul jika ada foto struk
- [ ] Modal struk: foto tampil, tombol download berfungsi
- [ ] Export CSV: file terunduh dengan semua kolom dan filter aktif

---

## 16. DASHBOARD — BREAKDOWN ITEM

- [ ] Tabel tampil dengan nama item, total, qty, frekuensi
- [ ] Switch periode (harian/mingguan/bulanan/custom)
- [ ] Custom date range berfungsi
- [ ] Sort by Total (default) dan by Qty
- [ ] Search nama item
- [ ] Data sesuai dengan transaksi yang sudah dicatat (butuh ada struk/items)

---

## 17. DASHBOARD — DOMPET

- [ ] List dompet dengan saldo computed tampil
- [ ] Saldo negatif tampil merah
- [ ] Tombol + Tambah Dompet → form modal → submit → dompet baru muncul
- [ ] Hapus dompet dengan saldo 0 → berhasil
- [ ] Hapus dompet dengan saldo > 0 → muncul pesan error "Hanya bisa jika saldo 0"
- [ ] Riwayat transfer tampil di bawah

---

## 18. DASHBOARD — PENGATURAN

- [ ] Budget harian/mingguan/bulanan tampil sesuai nilai di DB
- [ ] Edit nilai → klik Simpan → tampil "✅ Budget berhasil disimpan!"
- [ ] Nilai baru langsung terlihat di dashboard overview (setelah cache expire / refresh)

---

## 19. CACHE DASHBOARD

- [ ] Tambah transaksi baru (via bot) → buka dashboard → data sudah terupdate (cache flush otomatis)
- [ ] Edit transaksi di dashboard → refresh → data benar
- [ ] Hapus transaksi di dashboard → data hilang

---

## 20. AUTO-REKAP SCHEDULER

### 20.1 Rekap mingguan manual
```bash
php artisan recap:send --period=weekly
```
- [ ] Bot mengirim rekap ke OWNER_CHAT_ID

### 20.2 Rekap bulanan manual
```bash
php artisan recap:send --period=monthly
```
- [ ] Bot mengirim rekap bulanan

### 20.3 Cek schedule list
```bash
php artisan schedule:list
```
- [ ] Tampil 5 scheduled command (reminders:dispatch, subscriptions:run, debts:remind, recap weekly, recap monthly)

---

## 21. KEAMANAN

### 21.1 Webhook secret token
- [ ] Kirim request ke `/api/telegram/webhook` tanpa header `X-Telegram-Bot-Api-Secret-Token`
  - Ekspektasi: response 403

- [ ] Kirim dengan secret token yang salah
  - Ekspektasi: response 403

### 21.2 Rate limit webhook
- [ ] Kirim > 30 request dalam 1 menit ke webhook
  - Ekspektasi: response 429 `{"ok":false,"error":"Too many requests"}`

### 21.3 Dashboard password
- [ ] Akses `/dashboard` tanpa login → redirect ke `/dashboard/login`
- [ ] Login dengan password salah → muncul error
- [ ] Login dengan password benar → masuk dashboard
- [ ] Akses `/dashboard/logout` → session dihapus, redirect ke login

### 21.4 SQL Injection
- [ ] Coba kirim: `'; DROP TABLE transactions; --`
  - Ekspektasi: diproses oleh Gemini sebagai teks biasa, tidak merusak database

---

## 22. DEPLOY CPANEL — FINAL CHECK

- [ ] Webhook aktif: `curl https://api.telegram.org/bot{TOKEN}/getWebhookInfo` → URL sesuai
- [ ] Bot merespons pesan di Telegram (kirim `/start`)
- [ ] Dashboard bisa diakses via HTTPS
- [ ] Cron job terdaftar di cPanel (verify via Cron Jobs → list)
- [ ] `APP_DEBUG=false` di `.env` produksi
- [ ] Storage folder `receipts/` bisa diakses (foto struk muncul di dashboard)
- [ ] `php artisan schedule:run` tidak error ketika dijalankan manual

---

## CATATAN TESTING

| Fitur | Status | Catatan |
|-------|--------|---------|
| Catat expense teks | | |
| Catat income teks | | |
| Voice note | | |
| Foto struk (confidence tinggi) | | |
| Foto struk (confidence rendah) | | |
| Transfer dompet | | |
| Set budget | | |
| Undo (< 5 menit) | | |
| Undo (> 5 menit) | | |
| Reminder sekali | | |
| Reminder berulang | | |
| Langganan auto-charge | | |
| Utang + lunas | | |
| Piutang + lunas | | |
| Tabungan goal | | |
| Dashboard overview | | |
| Dashboard transaksi + filter | | |
| Dashboard export CSV | | |
| Dashboard breakdown item | | |
| Webhook secret validation | | |
| Rate limit | | |
| Dashboard password | | |
| Scheduler cron | | |
