<table>
    <thead>
        <tr>
            <th colspan="10">Laporan Absensi Siswa</th>
        </tr>
        <tr>
            <th colspan="10">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Guru</th>
            <th>Siswa</th>
            <th>Mapel</th>
            <th>Jurusan</th>
            <th>Kelas</th>
            <th>Status</th>
            <th>Jam Absen</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                <td>{{ $item->teacherAttendance->teacher->nama_lengkap ?? '-' }}</td>
                <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                <td>{{ $item->student->classroom->major->nama_jurusan ?? '-' }}</td>
                <td>{{ $item->student->classroom->nama_kelas ?? '-' }}</td>
                <td>{{ $item->status }}</td>
                <td>{{ $item->jam_absen ?? '-' }}</td>
                <td>{{ $item->keterangan ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
