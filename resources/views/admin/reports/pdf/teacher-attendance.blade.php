<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Absensi Guru</title>
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
    <h2>Laporan Absensi Guru</h2>
    <p>Periode: {{ $periodLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Guru</th>
                <th>Mapel</th>
                <th>Jurusan</th>
                <th>Kelas</th>
                <th>Pertemuan</th>
                <th>Status</th>
                <th>Jumlah Siswa Diabsen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->tanggal }}</td>
                    <td>{{ $item->teacher->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->subject->nama_mapel ?? '-' }}</td>
                    <td>{{ $item->classroom->major->nama_jurusan ?? '-' }}</td>
                    <td>{{ $item->classroom->nama_kelas ?? '-' }}</td>
                    <td>{{ $item->pertemuan }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->attendanceDetails->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
