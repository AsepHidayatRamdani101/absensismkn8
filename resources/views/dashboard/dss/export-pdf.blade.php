<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Analytics Pancawaluya</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 12px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }
    </style>
</head>

<body>
    <h1>Laporan Analytics Pancawaluya</h1>
    <div class="meta">
        <div>Dibuat: {{ $generatedAt->format('d-m-Y H:i:s') }}</div>
        <div>Filter: Tahun Ajaran {{ $filters['academic_year_id'] ?: 'Semua' }}, Semester
            {{ $filters['semester'] ?: 'Semua' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Bagian</th>
                <th>Metrik</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['section'] ?? '' }}</td>
                    <td>{{ $row['metric'] ?? '' }}</td>
                    <td>{{ $row['value'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
