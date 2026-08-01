<h3>Reward Transactions</h3>
<table width="100%" border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Siswa</th>
            <th>Kelas</th>
            <th>Reward</th>
            <th>Point</th>
            <th>Dimensi</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ optional($row->transaction_date)->format('d-m-Y') }}</td>
                <td>{{ $row->student?->nama_lengkap }}</td>
                <td>{{ $row->classroom?->nama_kelas }}</td>
                <td>{{ $row->rewardItem?->name }}</td>
                <td>{{ $row->point }}</td>
                <td>{{ collect((array) $row->dimension_payload)->pluck('dimension_name')->join(', ') }}</td>
                <td>{{ $row->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
