<?php

namespace App\Exports;

use App\Models\TeacherSubject;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExamScoresTemplateExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    public function __construct(
        private readonly array $rows,
        private readonly TeacherSubject $assignment,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['NIS', 'Nama Siswa', 'UTS', 'UAS'];
    }

    public function title(): string
    {
        $title = trim(($this->assignment->classroom?->nama_kelas ?? 'Kelas') . ' ' . ($this->assignment->subject?->nama_mapel ?? ''));

        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $title) ?: 'Nilai', 0, 31);
    }

    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
