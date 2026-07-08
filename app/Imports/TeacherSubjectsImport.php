<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TeacherSubjectsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $data = [
                'teacher_nip' => trim((string) ($row['teacher_nip'] ?? '')),
                'subject_kode_mapel' => trim((string) ($row['subject_kode_mapel'] ?? '')),
                'classroom_kode_kelas' => trim((string) ($row['classroom_kode_kelas'] ?? '')),
                'tahun_ajaran' => trim((string) ($row['tahun_ajaran'] ?? '')),
                'semester' => trim((string) ($row['semester'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'teacher_nip' => 'required|max:255',
                'subject_kode_mapel' => 'required|max:20',
                'classroom_kode_kelas' => 'required|max:20',
                'tahun_ajaran' => 'required|max:20',
                'semester' => 'required|in:Ganjil,Genap',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': ' . $validator->errors()->first(),
                ]);
            }

            $teacher = Teacher::where('nip', $data['teacher_nip'])->first();
            if (!$teacher) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': NIP guru tidak ditemukan (' . $data['teacher_nip'] . ').',
                ]);
            }

            $subject = Subject::where('kode_mapel', $data['subject_kode_mapel'])->first();
            if (!$subject) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': Kode mapel tidak ditemukan (' . $data['subject_kode_mapel'] . ').',
                ]);
            }

            $classroom = Classroom::where('kode_kelas', $data['classroom_kode_kelas'])->first();
            if (!$classroom) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': Kode kelas tidak ditemukan (' . $data['classroom_kode_kelas'] . ').',
                ]);
            }

            $academicYear = AcademicYear::where('tahun_ajaran', $data['tahun_ajaran'])
                ->where('semester', $data['semester'])
                ->first();

            if (!$academicYear) {
                throw ValidationException::withMessages([
                    'file' => 'Baris ' . ($index + 2) . ': Tahun ajaran/semester tidak ditemukan (' . $data['tahun_ajaran'] . ' - ' . $data['semester'] . ').',
                ]);
            }

            TeacherSubject::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'classroom_id' => $classroom->id,
                    'academic_year_id' => $academicYear->id,
                ],
                []
            );
         }
     }

    private function isRowEmpty(Collection $row): bool
    {
        return collect($row->toArray())
            ->filter(fn($value) => $value !== null && $value !== '')
            ->isEmpty();
    }
}
