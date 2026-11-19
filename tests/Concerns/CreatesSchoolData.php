<?php

namespace Tests\Concerns;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait CreatesSchoolData
{
    private int $sequence = 0;

    protected function seedRoles(): void
    {
        foreach (['admin', 'guru', 'siswa', 'wali_kelas', 'bk', 'kesiswaan', 'kurikulum', 'tu'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeYear(bool $active = true, string $semester = 'Ganjil', string $tahun = '2026/2027'): AcademicYear
    {
        return AcademicYear::query()->create([
            'tahun_ajaran' => $tahun,
            'semester' => $semester,
            'is_active' => $active,
        ]);
    }

    protected function makeClassroom(string $name = 'X TKJ 1', string $tingkat = 'X'): Classroom
    {
        $this->sequence++;
        $major = Major::query()->firstOrCreate(
            ['kode_jurusan' => 'TKJ'],
            ['nama_jurusan' => 'Teknik Komputer dan Jaringan', 'singkatan' => 'TKJ']
        );

        return Classroom::query()->create([
            'major_id' => $major->id,
            'kode_kelas' => 'K' . $this->sequence . '-' . md5($name),
            'nama_kelas' => $name,
            'tingkat' => $tingkat,
            'rombel' => (string) $this->sequence,
        ]);
    }

    protected function makeTeacher(string $nip, string $name, ?Classroom $waliOf = null, string $jabatan = 'guru'): Teacher
    {
        return Teacher::query()->create([
            'nip' => $nip,
            'nama_lengkap' => $name,
            'jabatan' => $jabatan,
            'jenis_kelamin' => 'L',
            'is_wali_kelas' => $waliOf !== null,
            'wali_classroom_id' => $waliOf?->id,
        ]);
    }

    protected function makeStudent(Classroom $classroom, string $nis, string $name, ?string $nisn = null): Student
    {
        return Student::query()->create([
            'nis' => $nis,
            'nisn' => $nisn ?? '00' . $nis,
            'nama_lengkap' => $name,
            'jenis_kelamin' => 'P',
            'classroom_id' => $classroom->id,
        ]);
    }

    protected function makeSubject(string $name, string $kategori = 'Umum'): Subject
    {
        $this->sequence++;

        return Subject::query()->create([
            'kode_mapel' => 'MP' . str_pad((string) $this->sequence, 3, '0', STR_PAD_LEFT),
            'nama_mapel' => $name,
            'kategori' => $kategori,
            'jam_per_minggu' => 2,
        ]);
    }

    protected function makeAssignment(Teacher $teacher, Subject $subject, Classroom $classroom, AcademicYear $year, array $attributes = []): TeacherSubject
    {
        return TeacherSubject::query()->create(array_merge([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'grading_method' => 'manual',
            'attendance_weight' => 30,
        ], $attributes));
    }

    protected function makeSchedule(TeacherSubject $assignment): Schedule
    {
        return Schedule::query()->create([
            'teacher_subject_id' => $assignment->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '08:30:00',
        ]);
    }

    protected function makeUser(string $username, string $name, array $roles): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $username,
            'password' => bcrypt('password-rahasia'),
        ]);
        $user->assignRole($roles);

        return $user;
    }
}
