<table>
    <thead>
        <tr>
            <th colspan="8">Laporan Izin Siswa</th>
        </tr>
        <tr>
            <th colspan="8">Periode: {{ $periodLabel }}</th>
        </tr>
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
