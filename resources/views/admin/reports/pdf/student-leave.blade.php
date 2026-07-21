<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Izin Siswa</title>
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
    <h2>Laporan Izin Siswa</h2>
    <p>Periode: {{ $periodLabel }}</p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Alasan</th>
                <th>Status</th>
                <th>Surat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                    <td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td>
                    <td>{{ $item->jenis_pengajuan }}</td>
                    <td>{{ optional($item->tanggal_mulai)->format('d-m-Y') }} s/d
                        {{ optional($item->tanggal_selesai)->format('d-m-Y') }}</td>
                    <td>{{ $item->alasan }}</td>
                    <td>{{ $item->status_pengajuan }}</td>
                    <td>{{ $item->foto_surat_path ? 'Ada Surat' : 'Hardfile' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
