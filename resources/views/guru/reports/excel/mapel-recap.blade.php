<table>
    <thead>
        <tr>
            <th colspan="7">Rekap Guru Mapel</th>
        </tr>
        <tr>
            <th colspan="7">Guru: {{ $teacher->nama_lengkap }}</th>
        </tr>
        <tr>
            <th colspan="7">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Mapel</th>
            <th>Kelas</th>
            <th>Siswa</th>
            <th>Status</th>
            <th>Jam Absen</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->teacherAttendance->tanggal ?? '-' }}</td>
                <td>{{ $item->teacherAttendance->subject->nama_mapel ?? '-' }}</td>
                <td>{{ $item->teacherAttendance->classroom->nama_kelas ?? '-' }}</td>
                <td>{{ $item->student->nama_lengkap ?? '-' }}</td>
                <td>{{ $item->status === 'Alpha' ? 'Alpa' : $item->status }}</td>
                <td>{{ $item->jam_absen ?? '-' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="7"></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Total</strong></td>
            <td><strong>{{ $totals['total'] }}</strong></td>
            <td><strong>Hadir</strong></td>
            <td><strong>{{ $totals['hadir'] }}</strong></td>
            <td><strong>Sakit/Izin/Alpa</strong></td>
            <td><strong>{{ $totals['sakit'] + $totals['izin'] + $totals['alpa'] }}</strong></td>
        </tr>
    </tbody>
</table>
