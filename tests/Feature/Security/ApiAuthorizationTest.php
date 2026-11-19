<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesSchoolData;

    public function test_account_without_role_cannot_read_or_write_school_data(): void
    {
        $this->seedRoles();
        $user = User::query()->create(['name' => 'Tanpa Role', 'email' => 'luar@example.com', 'password' => bcrypt('x')]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/students')->assertForbidden();
        $this->getJson('/api/v1/teachers')->assertForbidden();
        $this->getJson('/api/v1/reports/daily')->assertForbidden();
        $this->postJson('/api/v1/attendance/manual', [])->assertForbidden();
        $this->postJson('/api/v1/device/rfid', ['device_code' => 'X'])->assertForbidden();
    }

    public function test_student_only_sees_own_record(): void
    {
        $this->seedRoles();
        $classroom = $this->makeClassroom();
        $own = $this->makeStudent($classroom, '4001', 'Siswa Sendiri', '0040010001');
        $this->makeStudent($classroom, '4002', 'Siswa Lain', '0040020002');
        Sanctum::actingAs($this->makeUser('0040010001', 'Siswa Sendiri', ['siswa']));

        $this->getJson('/api/v1/students')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_teacher_only_sees_students_of_taught_or_homeroom_classes(): void
    {
        $this->seedRoles();
        $year = $this->makeYear();
        $taught = $this->makeClassroom('X TKJ 1');
        $other = $this->makeClassroom('X TKJ 2');
        $teacher = $this->makeTeacher('198001012010011010', 'Guru Mapel');
        $this->makeAssignment($teacher, $this->makeSubject('Bahasa Indonesia'), $taught, $year);
        $this->makeStudent($taught, '5001', 'Murid Diajar');
        $this->makeStudent($other, '5002', 'Murid Kelas Lain');
        Sanctum::actingAs($this->makeUser($teacher->nip, $teacher->nama_lengkap, ['guru']));

        $this->getJson('/api/v1/students')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_lengkap', 'Murid Diajar');
    }

    public function test_teacher_cannot_record_attendance_for_another_teachers_schedule(): void
    {
        $this->seedRoles();
        $year = $this->makeYear();
        $classroom = $this->makeClassroom();
        $owner = $this->makeTeacher('198001012010011011', 'Pemilik Jadwal');
        $intruder = $this->makeTeacher('198001012010011012', 'Guru Lain');
        $subject = $this->makeSubject('Fisika');
        $schedule = $this->makeSchedule($this->makeAssignment($owner, $subject, $classroom, $year));
        $student = $this->makeStudent($classroom, '6001', 'Murid');
        Sanctum::actingAs($this->makeUser($intruder->nip, $intruder->nama_lengkap, ['guru']));

        $this->postJson('/api/v1/attendance/manual', [
            'schedule_id' => $schedule->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id,
            'tanggal' => '2026-08-03',
            'pertemuan' => '1',
            'details' => [['student_id' => $student->id, 'status' => 'Alpha']],
        ])->assertForbidden();

        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_student_report_is_limited_to_own_record(): void
    {
        $this->seedRoles();
        $classroom = $this->makeClassroom();
        $own = $this->makeStudent($classroom, '7001', 'Sendiri', '0070010001');
        $other = $this->makeStudent($classroom, '7002', 'Orang Lain', '0070020002');
        Sanctum::actingAs($this->makeUser('0070010001', 'Sendiri', ['siswa']));

        $this->getJson('/api/v1/reports/student/' . $own->id)->assertOk();
        $this->getJson('/api/v1/reports/student/' . $other->id)->assertForbidden();
    }
}
