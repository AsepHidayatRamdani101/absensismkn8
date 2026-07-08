<table>
    <thead>
        <tr>
            <th colspan="9">Laporan Absensi Guru</th>
        </tr>
        <tr>
            <th colspan="9">Periode: {{ $periodLabel }}</th>
        </tr>
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
