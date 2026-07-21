<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Absen Siswa</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        h2 {
            margin: 0 0 10px;
        }

        .meta {
            margin-bottom: 12px;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }
    </style>
</head>

<body>
    <h2>Riwayat Absen Siswa</h2>

    <div class="meta">
        <strong>Nama Siswa:</strong> {{ $student->nama_lengkap }}<br>
        <strong>Kelas:</strong> {{ $student->classroom->nama_kelas ?? '-' }}
        ({{ $student->classroom->kode_kelas ?? '-' }})<br>
        <strong>Filter Tanggal:</strong> {{ $filters['tanggal_dari'] ?: '-' }} s.d.
        {{ $filters['tanggal_sampai'] ?: '-' }}<br>
        <strong>Filter Status:</strong> {{ $filters['status'] ?: 'Semua' }}
    </div>

    <div class="meta">
        <strong>Ringkasan:</strong>
        Total {{ $statusSummary['total'] ?? 0 }},
        Hadir {{ $statusSummary['hadir'] ?? 0 }},
        Sakit {{ $statusSummary['sakit'] ?? 0 }},
        Izin {{ $statusSummary['izin'] ?? 0 }},
        Alpa {{ $statusSummary['alpa'] ?? 0 }},
        Terlambat {{ $statusSummary['terlambat'] ?? 0 }},
        Persentase Hadir {{ number_format($statusSummary['persentase_hadir'] ?? 0, 2) }}%
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Mata Pelajaran</th>
                <th>Guru</th>
                <th>Kelas</th>
                <th>Status</th>
                <th>Jam Absen</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($histories as $history)
                @php
                    $attendance = $history->teacherAttendance;
                    $tanggal = $attendance?->tanggal;
                    $status = $history->status === 'Alpha' ? 'Alpa' : $history->status;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $tanggal ? \Carbon\Carbon::parse($tanggal)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $tanggal ? \Carbon\Carbon::parse($tanggal)->translatedFormat('l') : '-' }}</td>
                    <td>{{ $attendance?->subject?->nama_mapel ?? '-' }}</td>
                    <td>{{ $attendance?->teacher?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $attendance?->classroom?->nama_kelas ?? '-' }}</td>
                    <td>{{ $status }}</td>
                    <td>{{ $history->jam_absen ?? '-' }}</td>
                    <td>{{ $history->keterangan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center;">Belum ada riwayat absensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
