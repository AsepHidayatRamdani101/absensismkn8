<table>
    <thead>
        <tr>
            <th colspan="9">Laporan Agenda Guru</th>
        </tr>
        <tr>
            <th colspan="9">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Guru</th>
            <th>Mapel</th>
            <th>Kelas</th>
            <th>Materi</th>
            <th>Kehadiran Guru</th>
            <th>Tugas</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->tanggal }}</td>
                <td>{{ $item->teacher->nama_lengkap ?? '-' }}</td>
                <td>{{ $item->subject->nama_mapel ?? '-' }}</td>
                <td>{{ $item->classroom->nama_kelas ?? '-' }}</td>
                <td>{{ $item->materi_pembelajaran ?? '-' }}</td>
                <td>{{ $item->kehadiran_guru ?? 'Hadir' }}</td>
                <td>
                    @if ($item->tugas_file_path)
                        Ada File
                    @elseif($item->tugas_deskripsi)
                        {{ $item->tugas_deskripsi }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
