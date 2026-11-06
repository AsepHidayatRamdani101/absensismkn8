<!doctype html><html lang="id"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:11px}h2{margin:0 0 6px}table{width:100%;border-collapse:collapse;margin:8px 0 16px}th,td{border:1px solid #555;padding:5px;text-align:left}th{background:#eee}
</style></head><body>
<h2>Laporan Bulanan Siswa</h2>
<p>Nama: {{ $student->nama_lengkap }}<br>NIS: {{ $student->nis }}<br>Bulan: {{ $month }}<br>Wali Kelas: {{ $teacher->nama_lengkap }}</p>
<h3>Rekap Absensi</h3><table><tr><th>Hadir</th><th>Dispen</th><th>Terlambat</th><th>Bolos</th><th>Sakit</th><th>Izin</th><th>Alpa</th></tr><tr>
<td>{{ $attendance['hadir'] }}</td><td>{{ $attendance['dispen'] }}</td><td>{{ $attendance['terlambat'] }}</td><td>{{ $attendance['bolos'] }}</td><td>{{ $attendance['sakit'] }}</td><td>{{ $attendance['izin'] }}</td><td>{{ $attendance['alpa'] }}</td></tr></table>
<h3>Nilai Tugas / Harian</h3><table><tr>@foreach($grade as $key => $value)<th>{{ $key }}</th>@endforeach</tr><tr>@foreach($grade as $value)<td>{{ $value ?? '-' }}</td>@endforeach</tr></table>
</body></html>
