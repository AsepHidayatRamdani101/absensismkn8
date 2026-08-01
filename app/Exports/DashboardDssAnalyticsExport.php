<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DashboardDssAnalyticsExport implements FromCollection, WithHeadings
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(private readonly array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(function (array $row): array {
            return [
                'section' => (string) ($row['section'] ?? ''),
                'metric' => (string) ($row['metric'] ?? ''),
                'value' => (string) ($row['value'] ?? ''),
            ];
        });
    }

    public function headings(): array
    {
        return ['Bagian', 'Metrik', 'Nilai'];
    }
}
