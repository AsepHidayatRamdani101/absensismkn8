<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR Siswa - {{ $student->nama_lengkap }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;600;700;800&family=Sora:wght@500;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --ink-900: #072035;
            --ink-700: #0b3857;
            --cyan-500: #2fc7d1;
            --cyan-300: #87f0f6;
            --mint-400: #56d5bf;
            --paper: #f4f8fb;
            --white: #ffffff;
        }

        @page {
            size: A4;
            margin: 16mm;
        }

        body {
            margin: 0;
            font-family: "Barlow", sans-serif;
            color: #11253a;
            background:
                linear-gradient(180deg, rgba(47, 199, 209, 0.08), transparent 35%),
                repeating-linear-gradient(0deg, #ecf4fa, #ecf4fa 24px, #f7fbff 24px, #f7fbff 48px),
                var(--paper);
        }

        .toolbar {
            max-width: 980px;
            margin: 18px auto 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .toolbar a,
        .toolbar button {
            border: 0;
            border-radius: 999px;
            padding: 10px 16px;
            background: linear-gradient(135deg, var(--ink-700), var(--ink-900));
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .sheet {
            max-width: 980px;
            margin: 0 auto 22px;
            padding: 4mm;
            display: grid;
            grid-template-columns: repeat(2, 54mm);
            gap: 14mm;
            justify-content: center;
            box-sizing: border-box;
        }

        .card {
            width: 54mm;
            height: 86mm;
            border-radius: 5mm;
            overflow: hidden;
            box-shadow: 0 16px 28px rgba(9, 29, 49, 0.22);
            display: flex;
            flex-direction: column;
            position: relative;
            isolation: isolate;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            forced-color-adjust: none;
            background:
                radial-gradient(circle at 18% 88%, rgba(47, 199, 209, 0.55), transparent 35%),
                radial-gradient(circle at 82% 16%, rgba(86, 213, 191, 0.34), transparent 38%),
                linear-gradient(165deg, #0e2d44 2%, #0a2134 44%, #174f66 100%);
            color: var(--white);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                repeating-linear-gradient(35deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.05) 1px, transparent 1px, transparent 22px);
            pointer-events: none;
            z-index: 0;
        }

        .card>* {
            position: relative;
            z-index: 1;
        }

        .front-head,
        .back-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 8px 5px;
        }

        .logo {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.45);
        }

        .school {
            font-family: "Sora", sans-serif;
            font-size: 8.6px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            max-width: 155px;
            opacity: 0.95;
        }

        .subtitle {
            font-size: 7.3px;
            color: rgba(236, 247, 255, 0.9);
        }

        .photo-wrap {
            margin: 4px 8px 0;
            height: 50mm;
            border-radius: 3.6mm;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.32);
            background: rgba(255, 255, 255, 0.09);
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
            font-size: 10px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.92);
        }

        .name-strip {
            margin: 6px 8px 0;
            background: linear-gradient(90deg, rgba(47, 199, 209, 0.92), rgba(86, 213, 191, 0.86));
            color: #042334;
            border-radius: 2.6mm;
            padding: 4px 6px;
        }

        .name {
            font-family: "Sora", sans-serif;
            font-size: 10.5px;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
            word-break: break-word;
        }

        .role {
            margin-top: 2px;
            font-size: 7.6px;
            font-weight: 700;
            letter-spacing: 0.25px;
            text-transform: uppercase;
        }

        .identity-compact {
            margin-top: 2px;
            font-size: 7.2px;
            font-weight: 700;
            line-height: 1.25;
            color: rgba(7, 31, 48, 0.92);
            word-break: break-word;
        }

        .back-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 12px 9px 10px;
            text-align: center;
        }

        .qr {
            width: 33mm;
            height: 33mm;
            background: #fff;
            border-radius: 3mm;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 22px rgba(3, 16, 29, 0.28);
        }

        .connect {
            font-family: "Sora", sans-serif;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .scan-note {
            font-size: 7.6px;
            line-height: 1.3;
            color: rgba(235, 247, 255, 0.92);
        }

        .back-foot {
            margin-top: 4px;
            font-size: 7.2px;
            color: rgba(228, 246, 255, 0.86);
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

            .sheet {
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
        <a href="{{ route('students.index') }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak Kartu</button>
    </div>

    @php
        $schoolName = $schoolSetting?->nama_sekolah ?? 'SMK';
        $photoPath = !empty($student->foto) ? asset('storage/' . $student->foto) : null;
        $qrUrl = route('students.qr.show', ['token' => $student->qr_token]);
    @endphp

    <div class="sheet">
        <div class="card">
            <div class="front-head">
                <div>
                    <div class="school">{{ $schoolName }}</div>
                    <div class="subtitle">Kartu Identitas Siswa</div>
                </div>
                @if (!empty($schoolSetting?->logo))
                    <img src="{{ asset('storage/' . $schoolSetting->logo) }}" alt="Logo" class="logo">
                @endif
            </div>

            <div class="photo-wrap">
                @if ($photoPath)
                    <img src="{{ $photoPath }}" alt="Foto {{ $student->nama_lengkap }}" class="photo">
                @else
                    <div class="photo-empty">FOTO SISWA</div>
                @endif
            </div>

            <div class="name-strip">
                <div class="name">{{ $student->nama_lengkap }}</div>
                <div class="identity-compact">{{ $student->nisn ?: '-' }} -
                    {{ $student->classroom?->nama_kelas ?: '-' }}</div>
            </div>
        </div>

        <div class="card">
            <div class="back-head">
                <div>
                    <div class="school">{{ $schoolName }}</div>
                    <div class="subtitle">Verifikasi Identitas Digital</div>
                </div>
                @if (!empty($schoolSetting?->logo))
                    <img src="{{ asset('storage/' . $schoolSetting->logo) }}" alt="Logo" class="logo">
                @endif
            </div>

            <div class="back-body">
                <div class="qr">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(112)->margin(1)->generate($qrUrl) !!}
                </div>
                <div class="connect">Scan Me</div>
                <div class="scan-note">Scan QR untuk membuka profil siswa beserta foto identitas.</div>
                <div class="back-foot">{{ $student->classroom?->nama_kelas ?: '-' }}</div>
            </div>
        </div>
    </div>
</body>

</html>
