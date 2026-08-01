<!DOCTYPE html>
<html>

<head>
    <title>Violation Transactions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            font-size: 12px;
        }
    </style>
</head>

<body onload="window.print()">
    @include('admin.pancawaluya.violation_transactions.export_pdf', ['rows' => $rows])
</body>

</html>
