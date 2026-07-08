<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SchedulesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'teacher_subject_id' => $row['teacher_subject_id'] ?? null,
                'hari' => trim((string) ($row['hari'] ?? '')),
                'jam_mulai' => trim((string) ($row['jam_mulai'] ?? '')),
                'jam_selesai' => trim((string) ($row['jam_selesai'] ?? '')),
                'ruangan' => trim((string) ($row['ruangan'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'teacher_subject_id' => 'required|integer',
                'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
                'jam_mulai' => 'required|date_format:H:i',
                'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                'ruangan' => 'nullable|max:100',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $teacherSubject = TeacherSubject::find($data['teacher_subject_id']);
            if (!$teacherSubject) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': teacher_subject_id tidak ditemukan (' . $data['teacher_subject_id'] . ').',
                ]);
            }

            Schedule::updateOrCreate(
                [
                    'teacher_subject_id' => $data['teacher_subject_id'],
                    'hari' => $data['hari'],
                    'jam_mulai' => $data['jam_mulai'],
                    'jam_selesai' => $data['jam_selesai'],
                ],
                [
                    'ruangan' => $data['ruangan'] !== '' ? $data['ruangan'] : null,
                ]
            );
        }
    }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())->filter(fn($value) => $value !== null && $value !== '')->isEmpty();
    }
}
