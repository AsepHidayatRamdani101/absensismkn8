<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Siswa - {{ $classroom->nama_kelas }}</title>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #111827;
            background: #f8fafc;
        }

        .toolbar {
            max-width: 1120px;
            margin: 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .title {
            max-width: 1120px;
            margin: 0 auto 12px;
            font-size: 14px;
            color: #334155;
        }

        .grid {
            max-width: 1120px;
            margin: 0 auto 18px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            padding: 6px;
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 8px;
            min-height: 95px;
        }

        .qr {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
        }

        .meta {
            font-size: 10px;
            line-height: 1.35;
        }

        .meta .name {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .meta .school {
            color: #0f4c81;
            font-weight: 600;
            margin-bottom: 2px;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .title {
                margin-bottom: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <a
            href="{{ route('students.index', ['major_id' => request('major_id'), 'classroom_id' => $classroom->id]) }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak Semua</button>
    </div>

    <div class="title">
        <strong>Kelas:</strong> {{ $classroom->nama_kelas }}
        @if ($classroom->major)
            | <strong>Jurusan:</strong> {{ $classroom->major->nama_jurusan }}
        @endif
        | <strong>Total:</strong> {{ $students->count() }} siswa
    </div>

    <div class="grid">
        @foreach ($students as $student)
            <div class="card">
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(64)->margin(1)->generate(route('students.qr.show', ['token' => $student->qr_token])) !!}
                </div>
                <div class="meta">
                    <div class="school">{{ $schoolSetting?->nama_sekolah ?? 'Sekolah' }}</div>
                    <div class="name">{{ $student->nama_lengkap }}</div>
                    <div>NIS: {{ $student->nis ?: '-' }}</div>
                    <div>NISN: {{ $student->nisn ?: '-' }}</div>
                    <div>Kelas: {{ $student->classroom?->nama_kelas ?: '-' }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>

</html>
