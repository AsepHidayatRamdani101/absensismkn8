<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class StudentAttendanceReportExport implements FromView
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $filters,
        private readonly string $periodLabel
    ) {
    }

    public function view(): View
    {
        return view('admin.reports.excel.student-attendance', [
            'rows' => $this->rows,
            'filters' => $this->filters,
            'periodLabel' => $this->periodLabel,
        ]);
    }
}
