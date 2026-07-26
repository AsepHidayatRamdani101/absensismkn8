<table>
    <thead>
        <tr>
            <th colspan="10">Rekap Siswa Wali Kelas</th>
        </tr>
        <tr>
            <th colspan="10">Guru: {{ $teacher->nama_lengkap }}</th>
        </tr>
        <tr>
            <th colspan="10">Kelas: {{ $classroom->nama_kelas ?? '-' }}</th>
        </tr>
        <tr>
            <th colspan="10">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Orang Tua / Wali</th>
            <th>Hadir</th>
            <th>Sakit</th>
            <th>Izin</th>
            <th>Alpa</th>
            <th>Total</th>
            <th>% Hadir</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['student']->nis }}</td>
                <td>{{ $row['student']->nama_lengkap }}</td>
                <td>{{ $row['student']->nama_orang_tua_wali ?: '-' }}</td>
                <td>{{ $row['hadir'] }}</td>
                <td>{{ $row['sakit'] }}</td>
                <td>{{ $row['izin'] }}</td>
                <td>{{ $row['alpa'] }}</td>
                <td>{{ $row['total'] }}</td>
                <td>{{ $row['persen_hadir'] }}%</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4"><strong>Total</strong></td>
            <td><strong>{{ $totals['hadir'] }}</strong></td>
            <td><strong>{{ $totals['sakit'] }}</strong></td>
            <td><strong>{{ $totals['izin'] }}</strong></td>
            <td><strong>{{ $totals['alpa'] }}</strong></td>
            <td><strong>{{ $totals['total'] }}</strong></td>
            <td>-</td>
        </tr>
    </tbody>
</table>
