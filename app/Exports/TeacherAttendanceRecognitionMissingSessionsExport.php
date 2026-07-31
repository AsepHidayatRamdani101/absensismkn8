<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class TeacherAttendanceRecognitionMissingSessionsExport implements FromView
{
    public function __construct(
        private readonly Collection $rows,
        private readonly string $title,
        private readonly string $periodLabel,
        private readonly string $teacherName
    ) {
    }

    public function view(): View
    {
        return view('admin.reports.excel.teacher-attendance-recognition-missing-teacher-sessions', [
            'rows' => $this->rows,
            'title' => $this->title,
            'periodLabel' => $this->periodLabel,
            'teacherName' => $this->teacherName,
        ]);
    }
}
