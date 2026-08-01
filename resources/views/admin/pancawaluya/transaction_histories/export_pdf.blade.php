<h3>Transaction Histories</h3>
<table width="100%" border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Siswa</th>
            <th>Kelas</th>
            <th>Tipe</th>
            <th>Aksi</th>
            <th>Status</th>
            <th>Skor</th>
            <th>Sumber</th>
            <th>Aktor</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ optional($row->transaction_date)->format('d-m-Y') }}</td>
                <td>{{ $row->student?->nama_lengkap }}</td>
                <td>{{ $row->classroom?->nama_kelas }}</td>
                <td>{{ $row->reference_type }}</td>
                <td>{{ $row->action }}</td>
                <td>{{ $row->status }}</td>
                <td>{{ $row->score_before }} -> {{ $row->score_after }}</td>
                <td>{{ $row->source }}</td>
                <td>{{ $row->actor?->name }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
