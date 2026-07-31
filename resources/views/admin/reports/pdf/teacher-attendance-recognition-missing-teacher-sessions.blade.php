<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        h2 {
            margin: 0 0 6px;
        }

        .meta {
            margin: 0 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <h2>{{ $title }}</h2>
    <p class="meta"><strong>Guru:</strong> {{ $teacher->nama_lengkap ?? '-' }}</p>
    <p class="meta"><strong>Periode:</strong> {{ $periodLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Mapel</th>
                <th>Jurusan</th>
                <th>Kelas</th>
                <th>Absen Guru oleh Siswa (Kamera)</th>
                <th>Agenda Guru</th>
                <th>Absen Siswa oleh Guru</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['mapel'] }}</td>
                    <td>{{ $row['jurusan'] }}</td>
                    <td>{{ $row['kelas'] }}</td>
                    <td>{{ $row['absensi_guru_siswa_kamera'] ? 'Terisi' : 'Belum' }}</td>
                    <td>{{ $row['agenda_guru'] ? 'Terisi' : 'Belum' }}</td>
                    <td>{{ $row['absensi_siswa_oleh_guru'] ? 'Terisi' : 'Belum' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
