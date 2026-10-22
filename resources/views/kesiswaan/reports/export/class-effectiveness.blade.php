<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Efektivitas Kelas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2, h3, p { margin: 0 0 8px; }
        h3 { margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #444; padding: 4px; text-align: left; }
        th { background: #e9ecef; }
        .number { text-align: right; }
        .class-heading { margin-top: 16px; }
    </style>
</head>

<body>
    <h2>Laporan Efektivitas Belajar Kelas</h2>
    <p>Periode: {{ $periodLabel }}</p>
    <p>Efektivitas = (Hadir + Terlambat + Dispen) ÷ seluruh catatan status siswa. Dispen dihitung hadir.</p>

    @foreach ($report as $class)
        <h3 class="class-heading">{{ $class['classroom'] }} — {{ $class['major'] }}</h3>
        <p>
            Efektivitas belajar: {{ number_format($class['effectiveness'], 2) }}% |
            Catatan absensi: {{ $class['student_records'] }} |
            Catatan hadir: {{ $class['student_present_records'] }}
        </p>

        <table>
            <thead>
                <tr>
                    <th colspan="3">Persentase Kehadiran Siswa</th>
                </tr>
                <tr>
                    <th>Status</th>
                    <th class="number">Jumlah</th>
                    <th class="number">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($class['status_counts'] as $status)
                    <tr>
                        <td>{{ $status['label'] }}</td>
                        <td class="number">{{ $status['count'] }}</td>
                        <td class="number">{{ number_format($status['percentage'], 2) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th colspan="4">Kehadiran Guru</th>
                </tr>
                <tr>
                    <th>Nama Guru</th>
                    <th class="number">Sesi Hadir</th>
                    <th class="number">Sesi Jadwal Efektif</th>
                    <th class="number">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($class['teachers'] as $teacher)
                    <tr>
                        <td>{{ $teacher['name'] }}</td>
                        <td class="number">{{ $teacher['present'] }}</td>
                        <td class="number">{{ $teacher['expected'] }}</td>
                        <td class="number">{{ number_format($teacher['percentage'], 2) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada jadwal aktif pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>

</html>
