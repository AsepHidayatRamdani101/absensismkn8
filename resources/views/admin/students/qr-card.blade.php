<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR Siswa - {{ $student->nama_lengkap }}</title>
    <style>
        @page {
            size: A4;
            margin: 14mm;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #1f2937;
            background: #f3f4f6;
        }

        .toolbar {
            max-width: 900px;
            margin: 20px auto 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .toolbar a,
        .toolbar button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            background: #0f4c81;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .card-wrap {
            max-width: 900px;
            margin: 0 auto 24px;
            display: flex;
            justify-content: center;
        }

        .card {
            width: 95mm;
            height: 60mm;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 12px 24px rgba(15, 76, 129, 0.12);
            display: flex;
            flex-direction: column;
        }

        .card-head {
            background: #0f4c81;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            font-size: 11px;
        }

        .logo {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            object-fit: cover;
            background: #fff;
        }

        .card-body {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 72px;
            gap: 8px;
            padding: 8px 10px;
        }

        .meta {
            font-size: 11px;
            line-height: 1.35;
        }

        .meta b {
            display: inline-block;
            min-width: 44px;
        }

        .qr {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
        }

        .name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .hint {
            font-size: 9px;
            color: #6b7280;
            margin-top: 4px;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .card {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <a href="{{ route('students.index') }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak Kartu</button>
    </div>

    <div class="card-wrap">
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>{{ $schoolSetting?->nama_sekolah ?? 'Identitas Siswa' }}</strong><br>
                    <span>{{ $student->classroom?->nama_kelas ?? '-' }}</span>
                </div>
                @if (!empty($schoolSetting?->logo))
                    <img src="{{ asset('storage/' . $schoolSetting->logo) }}" alt="Logo" class="logo">
                @endif
            </div>

            <div class="card-body">
                <div class="meta">
                    <div class="name">{{ $student->nama_lengkap }}</div>
                    <div><b>NIS</b>: {{ $student->nis ?: '-' }}</div>
                    <div><b>NISN</b>: {{ $student->nisn ?: '-' }}</div>
                    <div><b>JK</b>: {{ $student->jenis_kelamin ?: '-' }}</div>
                    <div><b>Kelas</b>: {{ $student->classroom?->nama_kelas ?: '-' }}</div>
                    <div class="hint">Scan QR untuk validasi identitas.</div>
                </div>
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(68)->margin(1)->generate(route('students.qr.show', ['token' => $student->qr_token])) !!}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
