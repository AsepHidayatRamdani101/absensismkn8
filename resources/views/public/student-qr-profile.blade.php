<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identitas Siswa</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f6f9fc 0%, #e9f2ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 24px 0;
        }

        .identity-card {
            position: relative;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(13, 38, 76, 0.12);
            overflow: hidden;
        }

        .identity-watermark {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 1;
            opacity: 0.06;
        }

        .identity-watermark img {
            width: min(45vw, 280px);
            height: auto;
            object-fit: contain;
        }

        .identity-header {
            background: #0f4c81;
            color: #fff;
            padding: 16px 24px;
            position: relative;
            z-index: 2;
        }

        .identity-school-logo {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.45);
            margin-right: 8px;
            background: #fff;
        }

        .identity-body {
            position: relative;
            z-index: 2;
        }

        .student-photo {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #dbe7f4;
            background: #fff;
        }

        .identity-table td {
            padding: 0.45rem 0;
            vertical-align: top;
            border: 0;
        }

        .identity-table td:first-child {
            width: 38%;
            color: #4a5568;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card identity-card">
                    <div class="identity-header d-flex justify-content-between align-items-center flex-wrap">
                        <h1 class="h4 mb-1 d-flex align-items-center">
                            @if (!empty($schoolSetting?->logo))
                                <img src="{{ asset('storage/' . $schoolSetting->logo) }}" alt="Logo sekolah"
                                    class="identity-school-logo">
                            @endif
                            <span>{{ $schoolSetting?->nama_sekolah ?? 'Identitas Siswa' }}</span>
                        </h1>
                        <small class="text-light">Validasi dari QR Code</small>
                    </div>

                    @if (!empty($schoolSetting?->logo))
                        <div class="identity-watermark">
                            <img src="{{ asset('storage/' . $schoolSetting->logo) }}" alt="Watermark sekolah">
                        </div>
                    @endif

                    <div class="card-body p-4 p-md-5 identity-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                @php
                                    $hasPhoto = !empty($student->foto);
                                    $photoUrl = $hasPhoto ? asset('storage/' . $student->foto) : null;
                                @endphp
                                @if ($hasPhoto)
                                    <img src="{{ $photoUrl }}" alt="Foto {{ $student->nama_lengkap }}"
                                        class="student-photo">
                                @else
                                    <div class="student-photo d-inline-flex align-items-center justify-content-center">
                                        <span class="text-muted">Tidak ada foto</span>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-8">
                                <h2 class="h4 mb-3">{{ $student->nama_lengkap }}</h2>
                                <table class="table identity-table mb-0">
                                    <tr>
                                        <td>NIS</td>
                                        <td>: {{ $student->nis ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>NISN</td>
                                        <td>: {{ $student->nisn ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Jenis Kelamin</td>
                                        <td>: {{ $student->jenis_kelamin ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Kelas</td>
                                        <td>: {{ $student->classroom?->nama_kelas ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Jurusan</td>
                                        <td>: {{ $student->classroom?->major?->nama_jurusan ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>No HP Siswa</td>
                                        <td>: {{ $student->no_hp ?: '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted small mt-3 mb-0">Data diambil dari sistem absensi sekolah.</p>
            </div>
        </div>
    </div>
</body>

</html>
