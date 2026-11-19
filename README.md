# Sistem Absensi & Penilaian SMKN 8 Garut

Aplikasi web sekolah berbasis Laravel 12 untuk absensi siswa & guru (QR, KBM,
izin), agenda guru, PJJ, karakter Pancawaluya, penilaian harian, **nilai UTS/UAS**,
dan **cetak rapor semester**.

## Menjalankan di Komputer Lokal (Laragon)

Kebutuhan: PHP 8.2+, Composer, MySQL, Node.js (semuanya tersedia di Laragon).

```bash
composer install
cp .env.example .env            # atur DB_DATABASE/DB_USERNAME/DB_PASSWORD
php artisan key:generate
php artisan migrate --seed      # data contoh & akun demo (khusus lokal)
php artisan storage:link
npm install && npm run build
php artisan serve               # buka http://127.0.0.1:8000
```

Akun demo lokal: `admin@smkn8.sch.id` / `admin123` (jangan dipakai di server).

## Menjalankan Test

```bash
php artisan test
```

Test memakai SQLite in-memory, sehingga tidak menyentuh database lokal.

## Dokumentasi

- [Buku Panduan Aplikasi](docs/Buku-Panduan-Aplikasi.md) — cara pakai per peran (siswa, guru, wali kelas, kurikulum).
- [Panduan Deploy Production](docs/Panduan-Deploy-Production.md) — pemasangan di server, keamanan, backup, dan update.
- [Kontrak API v1](docs/API-v1-Contract.md)

## Fitur Penilaian & Rapor

| Peran | Menu | Fungsi |
|---|---|---|
| Guru mapel | Penilaian → Nilai UTS & UAS | Input/import Excel nilai UTS & UAS per kelas-mapel |
| Wali kelas | Wali → Rapor Semester | Cek kelengkapan nilai, catatan wali, cetak rapor PDF, leger Excel |
| Admin / Kurikulum | Rapor Semester | Pantau & cetak rapor semua kelas |
| Admin | Profil Sekolah → Pengaturan Rapor | Bobot Harian/UTS/UAS, KKM, kepala sekolah, tempat terbit |

Nilai Rapor = Harian × bobot + UTS × bobot + UAS × bobot (bawaan 50 : 25 : 25),
dengan predikat A/B/C/D berdasarkan KKM.
