<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Print Master Pelanggaran</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>

<body onload="window.print()" class="p-3">
    <h4>Master Pelanggaran</h4>
    <table class="table table-bordered table-sm">
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
</body>

</html>
