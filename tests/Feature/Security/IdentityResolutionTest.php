<?php

namespace Tests\Feature\Security;

use App\Models\Teacher;
use App\Support\SchoolIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class IdentityResolutionTest extends TestCase
{
    use RefreshDatabase;
    use CreatesSchoolData;

    public function test_teacher_is_resolved_by_nip_even_when_names_collide(): void
    {
        $this->seedRoles();
        $teacherB = $this->makeTeacher('196501011990031002', 'Asep Saepudin');
        $teacherA = $this->makeTeacher('197501012000031001', 'Asep Saepudin');
        $userA = $this->makeUser($teacherA->nip, 'Asep Saepudin', ['guru']);

        $this->assertTrue(SchoolIdentity::teacherFor($userA)->is($teacherA));
        $this->assertFalse(SchoolIdentity::teacherFor($userA)->is($teacherB));
    }

    public function test_teacher_can_be_resolved_by_nuptk(): void
    {
        $this->seedRoles();
        $teacher = Teacher::query()->create([
            'nip' => null,
            'nuptk' => '1234567890123456',
            'nama_lengkap' => 'Guru Honorer',
            'jenis_kelamin' => 'P',
        ]);
        $user = $this->makeUser('1234567890123456', 'Guru Honorer', ['guru']);

        $this->assertTrue(SchoolIdentity::teacherFor($user)->is($teacher));
    }

    public function test_name_fallback_only_applies_to_unique_names(): void
    {
        $this->seedRoles();
        $unique = $this->makeTeacher('197001012000031003', 'Dewi Lestari');
        $this->makeTeacher('197001012000031004', 'Asep Saepudin');
        $this->makeTeacher('197001012000031005', 'Asep Saepudin');

        $legacyUnique = $this->makeUser('akun-lama-1', 'Dewi Lestari', ['guru']);
        $legacyDuplicate = $this->makeUser('akun-lama-2', 'Asep Saepudin', ['guru']);

        $this->assertTrue(SchoolIdentity::teacherFor($legacyUnique)->is($unique));
        $this->assertNull(SchoolIdentity::teacherFor($legacyDuplicate));
    }

    public function test_student_is_resolved_by_nisn_then_nis(): void
    {
        $this->seedRoles();
        $classroom = $this->makeClassroom();
        $byNisn = $this->makeStudent($classroom, '3001', 'Rina', '0099887766');
        $byNis = $this->makeStudent($classroom, '3002', 'Rudi', '');

        $this->assertTrue(SchoolIdentity::studentFor($this->makeUser('0099887766', 'Rina', ['siswa']))->is($byNisn));
        $this->assertTrue(SchoolIdentity::studentFor($this->makeUser('3002', 'Rudi', ['siswa']))->is($byNis));
        $this->assertNull(SchoolIdentity::studentFor($this->makeUser('tidak-ada', 'Rina', ['siswa'])));
    }
}
