<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Absensi Siswa</title>
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
    <h2>Laporan Absensi Siswa</h2>
    <p>Periode: {{ $periodLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Guru</th>
                <th>Siswa</th>
                <th>Mapel</th>
                <th>Jurusan</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Jam Absen</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                    <td>{{ $item->teacherAttendance->teacher->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                    <td>{{ $item->student->classroom->major->nama_jurusan ?? '-' }}</td>
                    <td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->jam_absen ?? '-' }}</td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
