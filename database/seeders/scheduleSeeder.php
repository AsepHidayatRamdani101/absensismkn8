<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\TeacherSubject;
use Illuminate\Database\Seeder;

class scheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teacherSubjects = TeacherSubject::query()->take(3)->get();

        if ($teacherSubjects->isEmpty()) {
            return;
        }

        $slots = [
            ['hari' => 'Senin', 'jam_mulai' => '07:00', 'jam_selesai' => '08:30', 'ruangan' => 'R-101'],
            ['hari' => 'Senin', 'jam_mulai' => '08:30', 'jam_selesai' => '10:00', 'ruangan' => 'R-101'],
            ['hari' => 'Selasa', 'jam_mulai' => '07:00', 'jam_selesai' => '08:30', 'ruangan' => 'Lab TJKT 1'],
        ];

        foreach ($teacherSubjects as $index => $teacherSubject) {
            if (!isset($slots[$index])) {
                break;
            }

            $slot = $slots[$index];

            Schedule::updateOrCreate(
                [
                    'teacher_subject_id' => $teacherSubject->id,
                    'hari' => $slot['hari'],
                    'jam_mulai' => $slot['jam_mulai'],
                    'jam_selesai' => $slot['jam_selesai'],
                ],
                [
                    'ruangan' => $slot['ruangan'],
                ]
            );
        }
    }
}
