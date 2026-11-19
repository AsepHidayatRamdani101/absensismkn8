# Panduan Deploy Production — Sistem Absensi SMKN 8 Garut

Panduan ini untuk memasang aplikasi di server sekolah (VPS Linux atau server
lokal) secara aman. Untuk pengembangan di laptop (Laragon), lihat `README.md`.

---

## 1. Kebutuhan Server

| Komponen | Versi |
|---|---|
| PHP | 8.2 atau lebih baru, ekstensi: `bcmath ctype curl dom fileinfo gd intl mbstring openssl pdo_mysql tokenizer xml zip` |
| Composer | 2.x |
| Database | MySQL 8 atau MariaDB 10.6+ |
| Node.js | 18+ (hanya untuk build tampilan) |
| Web server | Nginx atau Apache, **document root diarahkan ke folder `public/`** |
| Lainnya | `mysqldump`, `gzip`, `zip` (dipakai backup otomatis) |

> Jangan arahkan document root ke folder utama proyek. Bila diarahkan ke folder
> utama, file `.env` (berisi password database) bisa diunduh orang lain.

---

## 2. Instalasi Pertama

```bash
git clone <repo> absensismkn8 && cd absensismkn8

composer install --no-dev --optimize-autoloader
cp .env.production.example .env        # lalu edit nilai bertanda <...>
php artisan key:generate

npm ci && npm run build                # build tampilan (juga membuat public/css/login-tailwind.css)

php artisan migrate --force
php artisan db:seed --force            # HANYA sekali: role, permission, dan akun admin
php artisan storage:link

php artisan optimize                   # cache config, route, view, event
```

Saat `db:seed` di production, password admin diambil dari `SEED_ADMIN_PASSWORD`.
Bila kosong, password acak dibuat dan **ditampilkan sekali di layar** — catat lalu
ganti setelah login pertama. Data contoh (siswa/guru demo) tidak ikut dibuat.

Hak akses folder (sesuaikan user web server, misalnya `www-data`):

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
chmod 600 .env
```

---

## 3. Proses Latar Belakang

**Scheduler** (backup harian/mingguan, pengingat WhatsApp, pembersihan file lama).
Tambahkan ke crontab user web server:

```cron
* * * * * cd /var/www/absensismkn8 && php artisan schedule:run >> /dev/null 2>&1
```

Untuk server Windows tersedia `scripts/windows/run-laravel-scheduler-hidden.vbs`
yang dijalankan tiap menit lewat Task Scheduler.

**Queue worker** (notifikasi Pancawaluya). Contoh Supervisor
`/etc/supervisor/conf.d/absensi-worker.conf`:

```ini
[program:absensi-worker]
command=php /var/www/absensismkn8/artisan queue:work --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/www/absensismkn8/storage/logs/worker.log
```

Bila server tidak bisa menjalankan proses latar (misalnya shared hosting), set
`QUEUE_CONNECTION=sync` di `.env`.

---

## 4. Contoh Konfigurasi Nginx

```nginx
server {
    server_name absensi.contoh-sekolah.sch.id;
    root /var/www/absensismkn8/public;
    index index.php;
    client_max_body_size 64M;          # samakan dengan post_max_size PHP (lihat di bawah)

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Pengumpulan tugas PJJ bisa berisi beberapa file (maks. 10 MB per file), jadi
atur juga `php.ini`: `upload_max_filesize = 10M`, `post_max_size = 64M`,
`max_file_uploads = 20`, dan `expose_php = Off`.

Pasang HTTPS (misalnya dengan Certbot/Let's Encrypt), lalu pastikan di `.env`:
`APP_URL=https://...`, `APP_FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`.
Bila memakai Cloudflare/reverse proxy, isi `TRUSTED_PROXIES` (misalnya `*`).

---

## 5. Daftar Periksa Keamanan

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] Database memakai user khusus aplikasi (bukan `root`) dengan password kuat.
- [ ] Akun demo dari lingkungan lokal (`admin123`, `guru123`, `siswa123`) **tidak ada** di server, atau passwordnya sudah diganti.
- [ ] Situs berjalan di HTTPS; `APP_FORCE_HTTPS=true` dan `SESSION_SECURE_COOKIE=true`.
- [ ] Cron scheduler aktif (cek `storage/app/backups/database` terisi setiap hari).
- [ ] File backup disalin rutin ke tempat lain (Google Drive/hardisk eksternal), bukan hanya di server.
- [ ] Setelah "Generate Akun", minta guru/siswa mengganti password default.

Fitur keamanan yang sudah aktif di aplikasi:

- Pendaftaran akun mandiri dan reset password via email dinonaktifkan (akun dikelola admin).
- Akun terhubung ke data guru/siswa lewat NIP/NUPTK/NISN/NIS; nama hanya dipakai sebagai cadangan bila tidak kembar.
- API membatasi data sesuai role (guru hanya kelasnya, siswa hanya dirinya); token API kedaluwarsa 30 hari.
- "Generate Akun" tidak lagi mereset password akun lama (reset massal harus dicentang eksplisit).
- Header keamanan (anti-clickjacking, nosniff, referrer policy) dan batas percobaan login.
- PDF laporan WhatsApp memakai nama file acak dan dihapus otomatis setelah 7 hari.
- Halaman publik QR siswa menyamarkan nomor HP.

---

## 6. Update ke Versi Baru

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan queue:restart
php artisan up
```

### Catatan update fitur Nilai UTS/UAS & Rapor

1. `php artisan migrate --force` menambahkan tabel `exam_scores`, `report_cards`, dan kolom pengaturan rapor di `school_settings`.
2. Login sebagai admin → **Profil Sekolah → Pengaturan Rapor**: periksa bobot Harian/UTS/UAS (bawaan 50/25/25, total harus 100), KKM (bawaan 75), tempat penerbitan, serta nama & NIP kepala sekolah.
3. Pastikan data **Pengampu** (guru-mapel-kelas) semester berjalan sudah lengkap dan setiap kelas memiliki wali kelas, karena rapor disusun dari data tersebut.

---

## 7. Pemulihan dari Backup

```bash
# Database (ganti nama file sesuai backup yang dipilih)
gunzip -c storage/app/backups/database/db_backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u <user> -p absensismkn8

# File upload (foto, lampiran) dari backup mingguan
unzip storage/app/backups/full/full_backup_YYYYMMDD_HHMMSS.zip -d /tmp/restore
cp -r /tmp/restore/app/* storage/app/
```
