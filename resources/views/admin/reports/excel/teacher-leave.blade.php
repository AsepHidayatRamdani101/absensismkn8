<table>
    <thead>
        <tr>
            <th colspan="7">Laporan Izin Guru</th>
        </tr>
        <tr>
            <th colspan="7">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Guru</th>
            <th>Jenis</th>
            <th>Tanggal</th>
            <th>Alasan</th>
            <th>Tugas</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->teacher->nama_lengkap ?? '-' }}</td>
                <td>{{ $item->jenis_pengajuan }}</td>
                <td>{{ optional($item->tanggal_mulai)->format('d-m-Y') }} s/d
                    {{ optional($item->tanggal_selesai)->format('d-m-Y') }}</td>
                <td>{{ $item->alasan }}</td>
                <td>
                    @if ($item->lampiran_tugas_path)
                        Ada File
                    @elseif($item->deskripsi_tugas)
                        {{ $item->deskripsi_tugas }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->status_pengajuan }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
