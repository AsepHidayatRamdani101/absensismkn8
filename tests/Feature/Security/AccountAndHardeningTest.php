<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class AccountAndHardeningTest extends TestCase
{
    use RefreshDatabase;
    use CreatesSchoolData;

    public function test_generating_teacher_accounts_keeps_existing_passwords(): void
    {
        $this->seedRoles();
        $existing = $this->makeTeacher('198001012010011020', 'Guru Lama');
        $this->makeTeacher('198001012010011021', 'Guru Baru');
        $existingUser = $this->makeUser($existing->nip, 'Guru Lama', ['guru']);
        $existingUser->forceFill(['password' => Hash::make('password-pilihan-sendiri')])->save();
        $admin = $this->makeUser('admin@sekolah.test', 'Admin', ['admin']);

        $this->actingAs($admin)->post(route('teachers.generate-accounts'))->assertRedirect();

        $this->assertTrue(Hash::check('password-pilihan-sendiri', $existingUser->fresh()->password));
        $newUser = User::query()->where('email', '198001012010011021')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('guru12345', $newUser->password));
        $this->assertTrue($newUser->hasRole('guru'));
    }

    public function test_generating_teacher_accounts_can_explicitly_reset_passwords(): void
    {
        $this->seedRoles();
        $existing = $this->makeTeacher('198001012010011030', 'Guru Lupa Password');
        $existingUser = $this->makeUser($existing->nip, 'Guru Lupa Password', ['guru']);
        $admin = $this->makeUser('admin@sekolah.test', 'Admin', ['admin']);

        $this->actingAs($admin)->post(route('teachers.generate-accounts'), ['reset_password' => 1])->assertRedirect();

        $this->assertTrue(Hash::check('guru12345', $existingUser->fresh()->password));
    }

    public function test_generating_student_accounts_keeps_existing_passwords(): void
    {
        $this->seedRoles();
        $classroom = $this->makeClassroom();
        $student = $this->makeStudent($classroom, '8001', 'Siswa Lama', '0080010001');
        $studentUser = $this->makeUser($student->nisn, 'Siswa Lama', ['siswa']);
        $studentUser->forceFill(['password' => Hash::make('rahasia-siswa')])->save();
        $admin = $this->makeUser('admin@sekolah.test', 'Admin', ['admin']);

        $this->actingAs($admin)
            ->postJson(route('students.generate-accounts'), ['after_id' => 0, 'batch_size' => 300])
            ->assertOk();

        $this->assertTrue(Hash::check('rahasia-siswa', $studentUser->fresh()->password));
    }

    public function test_responses_include_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=(self)', (string) $response->headers->get('Permissions-Policy'));
    }

    public function test_public_qr_profile_masks_phone_number(): void
    {
        $this->seedRoles();
        $classroom = $this->makeClassroom();
        $student = $this->makeStudent($classroom, '9001', 'Siswa QR');
        $student->forceFill(['no_hp' => '081234567890'])->save();
        $token = $student->fresh()->qr_token;

        $this->get(route('students.qr.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Siswa QR')
            ->assertDontSee('081234567890')
            ->assertSee('0812*****890');
    }
}
