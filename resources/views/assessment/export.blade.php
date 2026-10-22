<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Nilai dan Absensi</title>
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>{{ $kind === 'daily' ? 'Laporan Penilaian Harian' : ($kind === 'final' ? 'Laporan Penilaian Akhir' : 'Rekap Nilai Akhir dan Absensi') }}</h2>
    @php
        $fallbackHeadings = $kind === 'daily'
            ? ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Penilaian', 'Jenis', 'Tanggal', 'Nilai', 'Skor Maksimum', 'Bobot']
            : ($kind === 'final'
                ? ['Kelas', 'Guru', 'Mata Pelajaran', 'NIS', 'Siswa', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos']
                : ['NIS', 'Siswa', 'Mata Pelajaran', 'Guru', 'Metode Nilai', 'Nilai Akhir', 'Hadir/Terlambat/Dispen', 'Sakit', 'Izin', 'Alpa', 'Bolos']);
        $headings = array_keys($rows->first() ?? array_fill_keys($fallbackHeadings, ''));
    @endphp
    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headings) }}">Tidak ada data pada filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
