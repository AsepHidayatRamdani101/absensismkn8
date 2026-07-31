<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Export Akun Siswa</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        h2 {
            margin: 0 0 8px;
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
            background: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Daftar Akun Siswa</h2>
    <p class="meta">Total Data: {{ $students->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Jabatan</th>
                <th>Username Akun</th>
                <th>Status Akun</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $student->nis }}</td>
                    <td>{{ $student->nisn }}</td>
                    <td>{{ $student->nama_lengkap }}</td>
                    <td>{{ $student->classroom->nama_kelas ?? '-' }}</td>
                    <td>{{ $student->jabatan_kelas_label }}</td>
                    <td>{{ $student->username_akun ?: '-' }}</td>
                    <td>{{ $student->has_account ? 'Sudah' : 'Belum' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
