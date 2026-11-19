<?php

namespace Tests\Feature\Assessment;

use App\Models\AttendanceDetail;
use App\Models\ExamScore;
use App\Models\FinalGrade;
use App\Models\ReportCard;
use App\Models\TeacherAttendance;
use App\Services\Assessment\ReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class ReportCardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesSchoolData;

    private const SETTINGS = ['daily_weight' => 50, 'uts_weight' => 25, 'uas_weight' => 25, 'kkm' => 75];

    public function test_compose_weights_available_components(): void
    {
        $this->assertSame(80.0, ReportCardService::compose(80, 70, 90, self::SETTINGS)['score']);
        $partial = ReportCardService::compose(80, 70, null, self::SETTINGS);
        $this->assertSame(76.67, $partial['score']);
        $this->assertSame(['UAS'], $partial['missing']);
        $this->assertNull(ReportCardService::compose(null, null, null, self::SETTINGS)['score']);
        $noUts = ReportCardService::compose(80, null, 90, ['daily_weight' => 60, 'uts_weight' => 0, 'uas_weight' => 40]);
        $this->assertSame(84.0, $noUts['score']);
        $this->assertSame([], $noUts['missing']);
    }

    public function test_predicate_uses_kkm_based_intervals(): void
    {
        $this->assertSame('A', ReportCardService::predicate(92, 75));
        $this->assertSame('B', ReportCardService::predicate(91, 75));
        $this->assertSame('B', ReportCardService::predicate(84, 75));
        $this->assertSame('C', ReportCardService::predicate(83, 75));
        $this->assertSame('C', ReportCardService::predicate(75, 75));
        $this->assertSame('D', ReportCardService::predicate(74, 75));
        $this->assertNull(ReportCardService::predicate(null, 75));
    }

    private function scenario(): array
    {
        $this->seedRoles();
        $year = $this->makeYear();
        $classroom = $this->makeClassroom('X TKJ 1', 'X');
        $otherClass = $this->makeClassroom('X TKJ 2', 'X');
        $wali = $this->makeTeacher('197001012000011001', 'Pak Wali', $classroom);
        $otherWali = $this->makeTeacher('197001012000011002', 'Bu Wali Lain', $otherClass);
        $mathTeacher = $this->makeTeacher('198501012010011003', 'Bu Matematika');
        $math = $this->makeSubject('Matematika', 'Umum');
        $network = $this->makeSubject('Administrasi Jaringan', 'Kejuruan');
        $mathAssignment = $this->makeAssignment($mathTeacher, $math, $classroom, $year);
        $networkAssignment = $this->makeAssignment($mathTeacher, $network, $classroom, $year);
        $ani = $this->makeStudent($classroom, '1001', 'Ani');
        $budi = $this->makeStudent($classroom, '1002', 'Budi');
        $outsider = $this->makeStudent($otherClass, '2001', 'Cici');

        FinalGrade::query()->create(['teacher_subject_id' => $mathAssignment->id, 'student_id' => $ani->id, 'score' => 80]);
        ExamScore::query()->create(['teacher_subject_id' => $mathAssignment->id, 'student_id' => $ani->id, 'uts_score' => 70, 'uas_score' => 90]);
        FinalGrade::query()->create(['teacher_subject_id' => $networkAssignment->id, 'student_id' => $ani->id, 'score' => 90]);
        ExamScore::query()->create(['teacher_subject_id' => $networkAssignment->id, 'student_id' => $ani->id, 'uts_score' => 95, 'uas_score' => null]);

        $schedule = $this->makeSchedule($mathAssignment);
        foreach (['2026-08-03', '2026-08-03', '2026-08-04'] as $index => $date) {
            $session = TeacherAttendance::query()->create([
                'teacher_id' => $mathTeacher->id,
                'schedule_id' => $schedule->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $math->id,
                'academic_year_id' => $year->id,
                'tanggal' => $date,
                'pertemuan' => $index + 1,
                'status' => 'Selesai',
            ]);
            AttendanceDetail::query()->create(['teacher_attendance_id' => $session->id, 'student_id' => $ani->id, 'status' => 'Sakit']);
            AttendanceDetail::query()->create(['teacher_attendance_id' => $session->id, 'student_id' => $budi->id, 'status' => 'Hadir']);
        }

        return compact('year', 'classroom', 'otherClass', 'wali', 'otherWali', 'ani', 'budi', 'outsider', 'mathAssignment', 'networkAssignment');
    }

    public function test_service_builds_report_for_classroom(): void
    {
        $data = $this->scenario();

        $report = app(ReportCardService::class)->forClassroom($data['classroom'], $data['year']);

        $this->assertSame('1 (Ganjil)', $report['semester_label']);
        $this->assertSame('Pak Wali', $report['homeroom_teacher']->nama_lengkap);
        $this->assertSame(['Ani', 'Budi'], $report['entries']->pluck('student.nama_lengkap')->all());
        $this->assertSame(['Matematika', 'Administrasi Jaringan'], $report['subjects']->pluck('name')->all());

        $ani = $report['entries']->firstWhere('student.id', $data['ani']->id);
        $math = $ani['rows']->firstWhere('subject', 'Matematika');
        $this->assertSame(80.0, $math['final']);           // (80×50 + 70×25 + 90×25) / 100
        $this->assertSame('C', $math['predicate']);
        $this->assertTrue($math['passed']);
        $this->assertSame([], $math['missing']);

        $network = $ani['rows']->firstWhere('subject', 'Administrasi Jaringan');
        $this->assertSame(91.67, $network['final']);       // UAS kosong → (90×50 + 95×25) / 75
        $this->assertSame(['UAS'], $network['missing']);
        $this->assertSame(1, $ani['incomplete_subjects']);
        $this->assertSame(1, $ani['rank']);
        $this->assertSame(['sakit' => 2, 'izin' => 0, 'alpa' => 0], array_intersect_key($ani['attendance'], array_flip(['sakit', 'izin', 'alpa'])));

        $budi = $report['entries']->firstWhere('student.id', $data['budi']->id);
        $this->assertNull($budi['average']);
        $this->assertNull($budi['rank']);
    }

    public function test_homeroom_override_replaces_computed_attendance(): void
    {
        $data = $this->scenario();
        ReportCard::query()->create([
            'student_id' => $data['ani']->id,
            'academic_year_id' => $data['year']->id,
            'classroom_id' => $data['classroom']->id,
            'homeroom_note' => 'Pertahankan prestasimu.',
            'sick_days' => 3,
        ]);

        $report = app(ReportCardService::class)->forClassroom($data['classroom'], $data['year'], [$data['ani']->id]);
        $attendance = $report['entries']->first()['attendance'];

        $this->assertSame(3, $attendance['sakit']);
        $this->assertSame(0, $attendance['izin']);
        $this->assertTrue($attendance['is_override']);
        $this->assertSame(2, $attendance['computed']['sakit']);
    }

    public function test_homeroom_teacher_can_view_and_print_report_cards(): void
    {
        $data = $this->scenario();
        $user = $this->makeUser($data['wali']->nip, $data['wali']->nama_lengkap, ['guru']);

        $this->actingAs($user)
            ->get(route('guru.wali-kelas.report-cards'))
            ->assertOk()
            ->assertSee('Rapor Semester')
            ->assertSee('Ani')
            ->assertDontSee('Cici');

        $this->actingAs($user)
            ->get(route('guru.wali-kelas.report-cards.print', ['academic_year_id' => $data['year']->id, 'student_id' => $data['ani']->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('guru.wali-kelas.report-cards.print', ['academic_year_id' => $data['year']->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('guru.wali-kelas.report-cards.leger', ['academic_year_id' => $data['year']->id]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_homeroom_teacher_can_save_note_for_own_student_only(): void
    {
        $data = $this->scenario();
        $user = $this->makeUser($data['wali']->nip, $data['wali']->nama_lengkap, ['guru']);

        $this->actingAs($user)
            ->post(route('guru.wali-kelas.report-cards.note', $data['ani']), [
                'academic_year_id' => $data['year']->id,
                'classroom_id' => $data['classroom']->id,
                'homeroom_note' => 'Tingkatkan kedisiplinan.',
                'sick_days' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('report_cards', [
            'student_id' => $data['ani']->id,
            'academic_year_id' => $data['year']->id,
            'homeroom_note' => 'Tingkatkan kedisiplinan.',
            'sick_days' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('guru.wali-kelas.report-cards.note', $data['outsider']), [
                'academic_year_id' => $data['year']->id,
                'classroom_id' => $data['otherClass']->id,
                'homeroom_note' => 'Coba-coba.',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('guru.wali-kelas.report-cards.note', $data['outsider']), [
                'academic_year_id' => $data['year']->id,
                'classroom_id' => $data['classroom']->id,
                'homeroom_note' => 'Coba-coba.',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('report_cards', ['student_id' => $data['outsider']->id]);
    }

    public function test_teacher_without_homeroom_cannot_access_report_cards(): void
    {
        $data = $this->scenario();
        $teacher = $this->makeTeacher('199001012015011009', 'Guru Biasa');
        $user = $this->makeUser($teacher->nip, $teacher->nama_lengkap, ['guru']);

        $this->actingAs($user)->get(route('guru.wali-kelas.report-cards'))->assertForbidden();
        $this->actingAs($user)
            ->get(route('guru.wali-kelas.report-cards.print', ['academic_year_id' => $data['year']->id]))
            ->assertForbidden();
    }

    public function test_admin_can_print_any_class_and_kurikulum_is_read_only(): void
    {
        $data = $this->scenario();
        $admin = $this->makeUser('admin@sekolah.test', 'Admin', ['admin']);
        $kurikulum = $this->makeUser('198801012012011005', 'Waka Kurikulum', ['guru', 'kurikulum']);

        $this->actingAs($admin)
            ->get(route('report-cards.index', ['academic_year_id' => $data['year']->id, 'classroom_id' => $data['classroom']->id]))
            ->assertOk()
            ->assertSee('Ani');

        $this->actingAs($admin)
            ->get(route('report-cards.print', ['academic_year_id' => $data['year']->id, 'classroom_id' => $data['classroom']->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($kurikulum)
            ->get(route('report-cards.index', ['academic_year_id' => $data['year']->id, 'classroom_id' => $data['classroom']->id]))
            ->assertOk();

        $this->actingAs($kurikulum)
            ->post(route('report-cards.note', $data['ani']), [
                'academic_year_id' => $data['year']->id,
                'classroom_id' => $data['classroom']->id,
                'homeroom_note' => 'Catatan kurikulum',
            ])
            ->assertForbidden();
    }

    public function test_students_cannot_access_report_card_management(): void
    {
        $data = $this->scenario();
        $student = $this->makeUser($data['ani']->nisn, 'Ani', ['siswa']);

        $this->actingAs($student)->get(route('report-cards.index'))->assertForbidden();
        $this->actingAs($student)->get(route('guru.wali-kelas.report-cards'))->assertForbidden();
        $this->actingAs($student)
            ->get(route('report-cards.print', ['academic_year_id' => $data['year']->id, 'classroom_id' => $data['classroom']->id]))
            ->assertForbidden();
    }
}
