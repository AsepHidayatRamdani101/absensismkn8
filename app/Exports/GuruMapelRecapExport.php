<?php

namespace App\Exports;

use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class GuruMapelRecapExport implements FromView
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $totals,
        private readonly string $periodLabel,
        private readonly Teacher $teacher
    ) {
    }

    public function view(): View
    {
        return view('guru.reports.excel.mapel-recap', [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'periodLabel' => $this->periodLabel,
            'teacher' => $this->teacher,
        ]);
    }
}
