<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Siswa - {{ $classroom->nama_kelas }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800&family=Sora:wght@600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --ink-900: #072035;
            --ink-700: #0b3857;
            --cyan-500: #2fc7d1;
            --mint-400: #56d5bf;
            --paper: #f4f8fb;
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        body {
            margin: 0;
            font-family: "Barlow", sans-serif;
            color: #111827;
            background:
                linear-gradient(180deg, rgba(47, 199, 209, 0.08), transparent 32%),
                repeating-linear-gradient(0deg, #ecf4fa, #ecf4fa 24px, #f7fbff 24px, #f7fbff 48px),
                var(--paper);
        }

        .toolbar {
            max-width: 980px;
            margin: 14px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .toolbar a,
        .toolbar button {
            border: 0;
            border-radius: 999px;
            padding: 9px 14px;
            background: linear-gradient(135deg, var(--ink-700), var(--ink-900));
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .title {
            max-width: 980px;
            margin: 0 auto 10px;
            font-size: 13px;
            color: #334155;
        }

        .grid {
            max-width: 980px;
            margin: 0 auto 18px;
            padding: 3mm;
            display: grid;
            grid-template-columns: repeat(3, 54mm);
            gap: 6mm;
            justify-content: center;
            box-sizing: border-box;
        }

        .card {
            width: 54mm;
            height: 86mm;
            border-radius: 5mm;
            overflow: hidden;
            position: relative;
            background:
                radial-gradient(circle at 16% 88%, rgba(47, 199, 209, 0.52), transparent 34%),
                radial-gradient(circle at 82% 14%, rgba(86, 213, 191, 0.3), transparent 38%),
                linear-gradient(165deg, #0e2d44 2%, #0a2134 44%, #174f66 100%);
            color: #fff;
            box-shadow: 0 12px 22px rgba(9, 29, 49, 0.2);
            display: flex;
            flex-direction: column;
            isolation: isolate;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            forced-color-adjust: none;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(35deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.05) 1px, transparent 1px, transparent 22px);
            z-index: 0;
        }

        .card>* {
            position: relative;
            z-index: 1;
        }

        .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            padding: 7px 7px 4px;
        }

        .school {
            font-family: "Sora", sans-serif;
            font-size: 8.2px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
            max-width: 140px;
        }

        .logo {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.42);
        }

        .photo-wrap {
            margin: 0 7px;
            height: 27mm;
            border-radius: 3.4mm;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-empty {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.9);
        }

        .name-strip {
            margin: 5px 7px 0;
            border-radius: 2.5mm;
            background: linear-gradient(90deg, rgba(47, 199, 209, 0.92), rgba(86, 213, 191, 0.84));
            color: #08283a;
            padding: 3px 5px;
        }

        .name {
            font-family: "Sora", sans-serif;
            font-size: 8.8px;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
            word-break: break-word;
        }

        .identity-compact {
            margin-top: 2px;
            font-size: 6.8px;
            font-weight: 700;
            line-height: 1.2;
            color: rgba(8, 39, 58, 0.9);
            word-break: break-word;
        }

        .qr {
            margin: 4px auto 0;
            width: 24mm;
            height: 24mm;
            border-radius: 2.5mm;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            box-shadow: 0 8px 18px rgba(3, 16, 29, 0.26);
        }

        .foot {
            margin-top: 2px;
            margin-bottom: 4px;
            text-align: center;
            font-size: 6.7px;
            color: rgba(231, 245, 255, 0.9);
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                forced-color-adjust: none !important;
            }

            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .title {
                margin-bottom: 8px;
            }

            .grid {
                padding: 0;
            }

            .card {
                box-shadow: none;
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
                <div class="head">
                    <div class="school">{{ $schoolSetting?->nama_sekolah ?? 'Sekolah' }}</div>
                    @if (!empty($schoolSetting?->logo))
                        <img src="{{ asset('storage/' . $schoolSetting->logo) }}" alt="Logo" class="logo">
                    @endif
                </div>

                <div class="photo-wrap">
                    @if (!empty($student->foto))
                        <img src="{{ asset('storage/' . $student->foto) }}" alt="Foto {{ $student->nama_lengkap }}"
                            class="photo">
                    @else
                        <div class="photo-empty">FOTO SISWA</div>
                    @endif
                </div>

                <div class="name-strip">
                    <div class="name">{{ $student->nama_lengkap }}</div>
                    <div class="identity-compact">{{ $student->nisn ?: '-' }} -
                        {{ $student->classroom?->nama_kelas ?: '-' }}</div>
                </div>
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(72)->margin(1)->generate(route('students.qr.show', ['token' => $student->qr_token])) !!}
                </div>

                <div class="foot">Scan untuk verifikasi identitas</div>
            </div>
        @endforeach
    </div>
</body>

</html>
