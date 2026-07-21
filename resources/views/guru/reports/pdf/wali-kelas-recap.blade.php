<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rekap Siswa Wali Kelas</title>
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
    <h2>Rekap Siswa Wali Kelas</h2>
    <p>Guru: {{ $teacher->nama_lengkap }}</p>
    <p>Kelas: {{ $classroom->nama_kelas ?? '-' }}</p>
    <p>Periode: {{ $periodLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
                <th>Total</th>
                <th>% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['student']->nis }}</td>
                    <td>{{ $row['student']->nama_lengkap }}</td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['alpa'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['persen_hadir'] }}%</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>{{ $totals['hadir'] }}</strong></td>
                <td><strong>{{ $totals['sakit'] }}</strong></td>
                <td><strong>{{ $totals['izin'] }}</strong></td>
                <td><strong>{{ $totals['alpa'] }}</strong></td>
                <td><strong>{{ $totals['total'] }}</strong></td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
