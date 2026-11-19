<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportCardLegerExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private const HEADER_ROW = 4;

    public function __construct(private readonly array $report)
    {
    }

    public function array(): array
    {
        $classroom = $this->report['classroom'];
        $year = $this->report['year'];
        $subjects = $this->report['subjects'];

        $rows = [
            ['LEGER NILAI RAPOR'],
            ['Kelas: ' . $classroom->nama_kelas . ' | Tahun Pelajaran: ' . $year->tahun_ajaran . ' | Semester: ' . $this->report['semester_label']],
            ['Bobot nilai rapor — Harian ' . $this->report['settings']['daily_weight'] . '%, UTS ' . $this->report['settings']['uts_weight'] . '%, UAS ' . $this->report['settings']['uas_weight'] . '% | KKM ' . $this->report['settings']['kkm']],
            array_merge(
                ['No', 'NIS', 'NISN', 'Nama Siswa'],
                $subjects->pluck('name')->all(),
                ['Jumlah', 'Rata-rata', 'Peringkat', 'Sakit', 'Izin', 'Alpa', 'Mapel Belum Lengkap']
            ),
        ];

        foreach ($this->report['entries']->values() as $index => $entry) {
            $scores = $entry['rows']->keyBy('subject_id');
            $row = [
                $index + 1,
                (string) $entry['student']->nis,
                (string) $entry['student']->nisn,
                $entry['student']->nama_lengkap,
            ];

            foreach ($subjects as $subject) {
                $row[] = $scores->get($subject['subject_id'])['final_rounded'] ?? '';
            }

            $rows[] = array_merge($row, [
                $entry['graded_subjects'] > 0 ? $entry['total'] : '',
                $entry['average'] ?? '',
                $entry['rank'] ?? '',
                $entry['attendance']['sakit'],
                $entry['attendance']['izin'],
                $entry['attendance']['alpa'],
                $entry['incomplete_subjects'],
            ]);
        }

        return $rows;
    }

    public function title(): string
    {
        return mb_substr('Leger ' . preg_replace('/[\\\\\/\?\*\[\]:]/', '-', (string) $this->report['classroom']->nama_kelas), 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(4 + $this->report['subjects']->count() + 7);
                $lastRow = self::HEADER_ROW + $this->report['entries']->count();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A' . self::HEADER_ROW . ':' . $lastColumn . self::HEADER_ROW)->getFont()->setBold(true);
                $sheet->getStyle('A' . self::HEADER_ROW . ':' . $lastColumn . self::HEADER_ROW)
                    ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A' . self::HEADER_ROW . ':' . $lastColumn . $lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->freezePane('E' . (self::HEADER_ROW + 1));
            },
        ];
    }
}
