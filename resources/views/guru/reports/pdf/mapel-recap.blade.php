<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rekap Guru Mapel</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        h2,
        p {
            margin: 0 0 8px;
        }
    </style>
</head>

<body>
    <h2>Rekap Guru Mapel</h2>
    <p>Guru: {{ $teacher->nama_lengkap }}</p>
    <p>Periode: {{ $periodLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Mapel</th>
                <th>Kelas</th>
                <th>Siswa</th>
                <th>Status</th>
                <th>Jam Absen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                    <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                    <td>{{ $item->teacherAttendance->classroom->nama_kelas ?? '-' }}</td>
                    <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->status === 'Alpha' ? 'Alpa' : $item->status }}</td>
                    <td>{{ $item->jam_absen ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 8px;">
        Total: {{ $totals['total'] }} |
        Hadir: {{ $totals['hadir'] }} |
        Sakit: {{ $totals['sakit'] }} |
        Izin: {{ $totals['izin'] }} |
        Alpa: {{ $totals['alpa'] }}
    </p>
</body>

</html>
