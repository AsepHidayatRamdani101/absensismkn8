# Kontrak API v1

Dokumen ini merangkum standar respons API v1 agar konsumsi data di aplikasi mobile/web konsisten.

## Standar Envelope Respons

Semua endpoint v1 diarahkan menggunakan bentuk berikut:

```json
{
  "success": true,
  "message": "Deskripsi hasil",
  "data": {},
  "meta": {}
}
```

Catatan:
- `meta` opsional, dipakai untuk pagination atau info scope laporan.
- Untuk kompatibilitas lama, endpoint login masih menyediakan `token` dan `user` di level root selain `data`.

## Auth & Keamanan

- `POST /api/v1/login`
  - Throttle: `api-auth` (10 request/menit per kombinasi email+IP).
- Seluruh route ber-`auth:sanctum`:
  - Throttle: `api-default` (120 request/menit per user+IP).
- Route mutasi penting (`logout`, `attendance/manual`, `device/rfid`, `device/face`):
  - Middleware audit aktif (`audit`).

## Endpoint Yang Sudah Distandardisasi

- Auth:
  - `POST /api/v1/login`
  - `POST /api/v1/logout`
  - `GET /api/v1/profile`
- Master data:
  - `GET /api/v1/students` (resource + meta pagination)
  - `GET /api/v1/teachers` (resource + meta pagination)
- Attendance:
  - `POST /api/v1/attendance/manual`
  - `GET /api/v1/attendance/history` (resource + meta pagination)
- Reports:
  - `GET /api/v1/reports/daily`
  - `GET /api/v1/reports/monthly`
  - `GET /api/v1/reports/student/{student}`
- Device:
  - `POST /api/v1/device/rfid`
  - `POST /api/v1/device/face`

## Resource Ringkas

- `StudentResource`
  - id, nis, nisn, nama_lengkap, jenis_kelamin, classroom
- `TeacherResource`
  - id, nip, nama_lengkap, jabatan, jenis_kelamin, is_wali_kelas, wali_classroom_id
- `TeacherAttendanceResource`
  - id, tanggal, pertemuan, status, teacher, subject, classroom

## Scope Akses Data

- Siswa API (`/students`, `/reports/student/{student}`):
  - role siswa hanya boleh data milik sendiri.
- Guru API (`/students`, `/teachers`, `attendance/manual`):
  - data disesuaikan dengan relasi guru (mis. wali kelas) dan teacher_id dipaksa mengikuti akun guru pada absensi manual.

## Pagination Meta

Untuk endpoint list, gunakan meta berikut:

```json
{
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
  }
}
```

## Kode Status Umum

- `200`: berhasil.
- `401`: tidak terautentikasi / login gagal.
- `403`: tidak memiliki akses.
- `422`: validasi gagal.
- `429`: rate limit terlampaui.

## Contoh Request dan Response

### 1) Login

Request:

```json
POST /api/v1/login
{
  "email": "admin@example.com",
  "password": "password"
}
```

Response sukses:

```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "1|plain_text_token",
  "user": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@example.com",
    "role": "admin"
  },
  "data": {
    "token": "1|plain_text_token",
    "user": {
      "id": 1,
      "name": "Administrator",
      "email": "admin@example.com",
      "role": "admin"
    }
  }
}
```

### 2) Profile

Request:

```json
GET /api/v1/profile
Authorization: Bearer <token>
```

Response:

```json
{
  "success": true,
  "message": "Profil berhasil dimuat",
  "data": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

### 3) List Students (dengan pagination)

Request:

```json
GET /api/v1/students?per_page=20
Authorization: Bearer <token>
```

Response:

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "nis": "12345",
      "nisn": "99887766",
      "nama_lengkap": "Budi Santoso",
      "jenis_kelamin": "L",
      "classroom": {
        "id": 3,
        "nama_kelas": "XII TKJ 1",
        "tingkat": "XII",
        "jurusan": "TJKT"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
  }
}
```

### 4) Report Daily

Request:

```json
GET /api/v1/reports/daily
Authorization: Bearer <token>
```

Response:

```json
{
  "success": true,
  "message": "Laporan harian berhasil dimuat",
  "data": {
    "tanggal": "2026-08-01",
    "hadir": 120,
    "izin": 10,
    "sakit": 5,
    "alpha": 2
  },
  "meta": {
    "scope": "daily"
  }
}
```

### 5) Device Face

Request:

```json
POST /api/v1/device/face
Authorization: Bearer <token>
{
  "student_id": 10,
  "device_code": "FACE-01",
  "confidence": 0.98
}
```

Response:

```json
{
  "success": true,
  "message": "Face recognition berhasil",
  "data": {
    "student_id": 10,
    "device_code": "FACE-01",
    "confidence": 0.98,
    "timestamp": "2026-08-01T10:00:00+07:00"
  }
}
```

## Pengujian

- Validasi middleware route hardening: `tests/Feature/Api/ApiRouteHardeningTest.php`.
- Validasi perilaku rate limit aktual (429): `tests/Feature/Api/ApiRateLimitBehaviorTest.php`.
- Validasi kontrak envelope respons auth/device: `tests/Feature/Api/ApiResponseContractTest.php`.
- Validasi kontrol akses endpoint terlindungi (401): `tests/Feature/Api/ApiAccessControlTest.php`.
