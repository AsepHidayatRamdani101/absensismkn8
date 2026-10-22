# Deployment Checklist (Local, Staging, Production)

## Local
1. composer install
2. copy .env.example .env
3. php artisan key:generate
4. php artisan migrate
5. npm install && npm run build
6. php artisan test

## Staging
1. Sinkron branch release
2. Set APP_ENV=staging, APP_DEBUG=false
3. Jalankan migrate --force
4. Build assets production
5. Warm cache config, route, view
6. Jalankan smoke test modul inti

## Production
1. Aktifkan maintenance mode
2. Pull/tag release
3. composer install --no-dev --optimize-autoloader
4. php artisan migrate --force
5. php artisan optimize
6. Pastikan cron Laravel Scheduler aktif (konfigurasi di bawah)
7. Restart queue workers
8. Nonaktifkan maintenance mode
9. Verifikasi health endpoint /up
10. Pantau log 30 menit

### Laravel Scheduler (wajib untuk otomatisasi)

Jalankan `schedule:run` setiap menit melalui cron hosting. Gunakan path aplikasi dan PHP CLI
yang diberikan panel hosting; contoh shared hosting:

```cron
* * * * * cd /home/USERNAME/path-ke-aplikasi && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Pastikan perintah memakai direktori root Laravel (lokasi `artisan`), bukan direktori `public`.
Pada cPanel, masukkan bagian setelah lima kolom waktu ke kolom **Command** Cron Jobs.
Jika lokasi PHP berbeda, gunakan PHP CLI path dari panel hosting.

`php artisan schedule:work` adalah worker foreground untuk pengembangan/local dan tidak
perlu dijalankan pada shared hosting. Jangan mendaftarkan cron `schedule:run` lebih dari
satu kali untuk aplikasi/environment yang sama. Zona waktu aplikasi harus sesuai zona
waktu jadwal sekolah.

Di Windows/Laragon lokal, gunakan Task Scheduler yang menjalankan
`php artisan schedule:run` setiap menit secara tersembunyi; tidak perlu membuka
`schedule:work` di terminal.

## Rollback
1. Aktifkan maintenance mode
2. Checkout tag release sebelumnya
3. composer install --no-dev
4. php artisan migrate:rollback --step=1 (jika perlu)
5. Restore backup jika mismatch data
6. php artisan optimize
7. Nonaktifkan maintenance mode
