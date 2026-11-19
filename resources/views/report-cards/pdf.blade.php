<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor {{ $report['classroom']->nama_kelas }} - {{ $report['year']->tahun_ajaran }} {{ $report['year']->semester }}</title>
    <style>
        @page { margin: 14mm 15mm 14mm 15mm; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5px; color: #111; line-height: 1.35; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table { border-collapse: collapse; width: 100%; }
        .kop td { vertical-align: middle; }
        .kop .logo { width: 62px; }
        .kop .logo img { width: 56px; height: auto; }
        .kop .school-name { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        .kop .school-meta { font-size: 8.5px; color: #333; }
        .kop-line { border-top: 2px solid #111; border-bottom: 1px solid #111; height: 3px; margin: 6px 0 10px; }
        h1 { font-size: 12px; text-align: center; margin: 0; letter-spacing: .5px; }
        .subtitle { text-align: center; font-size: 9.5px; margin: 2px 0 10px; }
        .identity { table-layout: fixed; }
        .identity td { padding: 1.5px 2px; vertical-align: top; }
        .identity .label { width: 17%; }
        .identity .label-right { width: 15%; }
        .identity .sep { width: 2%; }
        .identity .value { width: 38%; }
        .identity .value-right { width: 26%; }
        .section-title { font-weight: bold; font-size: 10px; margin: 10px 0 4px; }
        .grades th, .grades td { border: 1px solid #333; padding: 3px 4px; }
        .grades th { background: #e9ecef; font-size: 8.5px; text-align: center; }
        .grades .group td { background: #f5f5f5; font-weight: bold; }
        .center { text-align: center; }
        .muted { color: #555; }
        .warn { color: #a15c00; }
        .summary td { font-weight: bold; }
        .legend { font-size: 8px; color: #333; margin-top: 4px; }
        .attendance td { border: 1px solid #333; padding: 3px 6px; }
        .attendance .label { width: 70%; }
        .note-box { border: 1px solid #333; min-height: 44px; padding: 6px; }
        .signatures { margin-top: 14px; }
        .signatures td { width: 33.33%; text-align: center; vertical-align: top; padding: 0 4px; }
        .sign-space { height: 50px; }
        .sign-name { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
@php
    $settings = $report['settings'];
    $kkm = $settings['kkm'];
    $range = (100 - $kkm) / 3;
    $thresholdA = (int) ceil($kkm + 2 * $range - 0.0001);
    $thresholdB = (int) ceil($kkm + $range - 0.0001);
    $format = fn ($value) => $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',');
    $homeroom = $report['homeroom_teacher'];
    $principal = $report['principal'];
    $place = trim((string) ($settings['city'] ?? ''));
    $dateText = ($place !== '' ? $place . ', ' : '') . $printDate->translatedFormat('j F Y');
@endphp

@foreach ($report['entries'] as $entry)
    @php
        $student = $entry['student'];
        $number = 0;
    @endphp
    <div class="page">
        <table class="kop">
            <tr>
                @if ($logo)
                    <td class="logo"><img src="{{ $logo }}" alt="Logo"></td>
                @endif
                <td>
                    <div class="school-name">{{ $school?->nama_sekolah ?? 'Nama Sekolah' }}</div>
                    <div class="school-meta">
                        {{ $school?->alamat }}
                        @if ($school?->npsn) · NPSN {{ $school->npsn }} @endif
                        @if ($school?->telepon) · Telp. {{ $school->telepon }} @endif
                        @if ($school?->email) · {{ $school->email }} @endif
                    </div>
                </td>
            </tr>
        </table>
        <div class="kop-line"></div>

        <h1>LAPORAN HASIL BELAJAR PESERTA DIDIK</h1>
        <div class="subtitle">Semester {{ $report['semester_label'] }} · Tahun Pelajaran {{ $report['year']->tahun_ajaran }}</div>

        <table class="identity">
            <tr>
                <td class="label">Nama Peserta Didik</td><td class="sep">:</td><td class="value"><strong>{{ $student->nama_lengkap }}</strong></td>
                <td class="label-right">Kelas</td><td class="sep">:</td><td class="value-right">{{ $report['classroom']->nama_kelas }}</td>
            </tr>
            <tr>
                <td class="label">NIS / NISN</td><td class="sep">:</td><td class="value">{{ $student->nis ?: '-' }} / {{ $student->nisn ?: '-' }}</td>
                <td class="label-right">Semester</td><td class="sep">:</td><td class="value-right">{{ $report['semester_label'] }}</td>
            </tr>
            <tr>
                <td class="label">Program Keahlian</td><td class="sep">:</td><td class="value">{{ $report['classroom']->major?->nama_jurusan ?? '-' }}</td>
                <td class="label-right">Tahun Pelajaran</td><td class="sep">:</td><td class="value-right">{{ $report['year']->tahun_ajaran }}</td>
            </tr>
        </table>

        <div class="section-title">A. Nilai Akademik</div>
        <table class="grades">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Mata Pelajaran</th>
                    <th style="width: 9%">Harian</th>
                    <th style="width: 9%">UTS</th>
                    <th style="width: 9%">UAS</th>
                    <th style="width: 10%">Nilai Akhir</th>
                    <th style="width: 9%">Predikat</th>
                    <th style="width: 15%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entry['groups'] as $groupLabel => $rows)
                    <tr class="group"><td colspan="8">{{ $groupLabel }}</td></tr>
                    @foreach ($rows as $row)
                        @php $number++; @endphp
                        <tr>
                            <td class="center">{{ $number }}</td>
                            <td>{{ $row['subject'] }}</td>
                            <td class="center">{{ $format($row['daily']) }}</td>
                            <td class="center">{{ $format($row['uts']) }}</td>
                            <td class="center">{{ $format($row['uas']) }}</td>
                            <td class="center"><strong>{{ $row['final_rounded'] ?? '-' }}</strong></td>
                            <td class="center">{{ $row['predicate'] ?? '-' }}</td>
                            <td class="center">
                                @if ($row['final_rounded'] === null)
                                    <span class="muted">Belum ada nilai</span>
                                @elseif ($row['missing'])
                                    <span class="warn">{{ $row['passed'] ? 'Tuntas' : 'Belum Tuntas' }}*</span>
                                @else
                                    {{ $row['passed'] ? 'Tuntas' : 'Belum Tuntas' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="8" class="center muted">Belum ada mata pelajaran untuk kelas ini.</td></tr>
                @endforelse
                <tr class="summary">
                    <td colspan="5" style="text-align: right">Jumlah</td>
                    <td class="center">{{ $entry['graded_subjects'] ? $entry['total'] : '-' }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="summary">
                    <td colspan="5" style="text-align: right">Rata-rata</td>
                    <td class="center">{{ $format($entry['average']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        <div class="legend">
            KKM {{ $kkm }} · Predikat: A = {{ $thresholdA }}–100, B = {{ $thresholdB }}–{{ $thresholdA - 1 }}, C = {{ $kkm }}–{{ $thresholdB - 1 }}, D = &lt; {{ $kkm }}.
            Nilai Akhir = Harian {{ $settings['daily_weight'] }}% + UTS {{ $settings['uts_weight'] }}% + UAS {{ $settings['uas_weight'] }}%.
            @if ($entry['incomplete_subjects'] > 0)
                <br>* Komponen nilai belum lengkap; Nilai Akhir dihitung dari komponen yang tersedia.
            @endif
        </div>

        <div class="section-title">B. Ketidakhadiran</div>
        <table class="attendance" style="width: 55%">
            <tr><td class="label">Sakit</td><td class="center">{{ $entry['attendance']['sakit'] }} hari</td></tr>
            <tr><td class="label">Izin</td><td class="center">{{ $entry['attendance']['izin'] }} hari</td></tr>
            <tr><td class="label">Tanpa Keterangan</td><td class="center">{{ $entry['attendance']['alpa'] }} hari</td></tr>
        </table>

        <div class="section-title">C. Catatan Wali Kelas</div>
        <div class="note-box">{!! nl2br(e($entry['report_card']?->homeroom_note ?? '')) ?: '&nbsp;' !!}</div>

        <table class="signatures">
            <tr>
                <td>
                    Mengetahui,<br>Orang Tua/Wali
                    <div class="sign-space"></div>
                    <div>(.................................)</div>
                </td>
                <td></td>
                <td>
                    {{ $dateText }}<br>Wali Kelas
                    <div class="sign-space"></div>
                    <div class="sign-name">{{ $homeroom?->nama_lengkap ?? '.................................' }}</div>
                    @if ($homeroom?->nip)<div>NIP. {{ $homeroom->nip }}</div>@endif
                </td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-top: 8px">
                    Mengetahui,<br>Kepala Sekolah
                    <div class="sign-space"></div>
                    <div class="sign-name">{{ $principal['name'] ?? '.................................' }}</div>
                    @if (!empty($principal['nip']))<div>NIP. {{ $principal['nip'] }}</div>@endif
                </td>
                <td></td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
