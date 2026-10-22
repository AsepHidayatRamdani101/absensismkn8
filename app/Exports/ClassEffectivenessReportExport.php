<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassEffectivenessReportExport implements FromView, WithTitle
{
    public function __construct(
        private readonly Collection $report,
        private readonly string $periodLabel
    ) {
    }

    public function view(): View
    {
        return view('kesiswaan.reports.export.class-effectiveness', [
            'report' => $this->report,
            'periodLabel' => $this->periodLabel,
        ]);
    }

    public function title(): string
    {
        return 'Efektivitas Kelas';
    }
}
