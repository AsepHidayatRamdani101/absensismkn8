<table>
    <thead>
        <tr>
            <th colspan="8">{{ $title }}</th>
        </tr>
        <tr>
            <th colspan="8">Guru: {{ $teacherName }}</th>
        </tr>
        <tr>
            <th colspan="8">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Mapel</th>
            <th>Jurusan</th>
            <th>Kelas</th>
            <th>Absen Guru oleh Siswa (Kamera)</th>
            <th>Agenda Guru</th>
            <th>Absen Siswa oleh Guru</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['tanggal'] }}</td>
                <td>{{ $row['mapel'] }}</td>
                <td>{{ $row['jurusan'] }}</td>
                <td>{{ $row['kelas'] }}</td>
                <td>{{ $row['absensi_guru_siswa_kamera'] ? 'Terisi' : 'Belum' }}</td>
                <td>{{ $row['agenda_guru'] ? 'Terisi' : 'Belum' }}</td>
                <td>{{ $row['absensi_siswa_oleh_guru'] ? 'Terisi' : 'Belum' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
