<h3>Master Pelanggaran</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="6">
    <thead>
        <tr>
            <th>Kode</th>
            <th>Kategori</th>
            <th>Nama</th>
            <th>Point</th>
            <th>Dimensi</th>
            <th>Weight</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            @php $mapping = $row->mappings->first(); @endphp
            <tr>
                <td>{{ $row->code }}</td>
                <td>{{ $row->category?->name }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $row->point }}</td>
                <td>{{ $mapping?->dimension?->name }}</td>
                <td>{{ $mapping?->weight }}</td>
                <td>{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
