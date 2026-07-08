<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Database\Seeder;

class teacherSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teacher = Teacher::query()->first();
        $classroom = Classroom::query()->first();
        $academicYear = AcademicYear::query()->where('is_active', true)->first() ?? AcademicYear::query()->first();

        if (!$teacher || !$classroom || !$academicYear) {
            return;
        }

        $subjectCodes = ['MAT-X', 'BIN-X', 'PBTGM-X'];

        $subjects = Subject::query()
            ->whereIn('kode_mapel', $subjectCodes)
            ->get();

        foreach ($subjects as $subject) {
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
}
