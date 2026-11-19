<?php

namespace Tests\Feature\Assessment;

use App\Models\ExamScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesSchoolData;
use Tests\TestCase;

class ExamScoreTest extends TestCase
{
    use RefreshDatabase;
    use CreatesSchoolData;

    private function scenario(): array
    {
        $this->seedRoles();
        $year = $this->makeYear();
        $classroom = $this->makeClassroom();
        $otherClass = $this->makeClassroom('X TKJ 2');
        $teacher = $this->makeTeacher('198001012010011001', 'Bu Siti');
        $otherTeacher = $this->makeTeacher('198001012010011002', 'Pak Dedi');
        $subject = $this->makeSubject('Matematika');
        $assignment = $this->makeAssignment($teacher, $subject, $classroom, $year);
        $otherAssignment = $this->makeAssignment($otherTeacher, $subject, $otherClass, $year);
        $studentA = $this->makeStudent($classroom, '1001', 'Ani');
        $studentB = $this->makeStudent($classroom, '1002', 'Budi');
        $outsider = $this->makeStudent($otherClass, '2001', 'Cici');
        $user = $this->makeUser($teacher->nip, $teacher->nama_lengkap, ['guru']);

        return compact('year', 'classroom', 'teacher', 'assignment', 'otherAssignment', 'studentA', 'studentB', 'outsider', 'user');
    }

    public function test_teacher_can_open_exam_page_for_own_assignment(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->get(route('guru.assessments.exams', ['teacher_subject_id' => $data['assignment']->id]))
            ->assertOk()
            ->assertSee('Nilai UTS')
            ->assertSee('Ani')
            ->assertSee('Budi')
            ->assertDontSee('Cici');
    }

    public function test_teacher_cannot_open_other_teachers_assignment(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->get(route('guru.assessments.exams', ['teacher_subject_id' => $data['otherAssignment']->id]))
            ->assertForbidden();
    }

    public function test_teacher_can_save_uts_and_uas_scores(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->post(route('guru.assessments.exams.update', $data['assignment']), [
                'scores' => [
                    $data['studentA']->id => ['uts' => '80', 'uas' => '92.5'],
                    $data['studentB']->id => ['uts' => '', 'uas' => ''],
                ],
            ])
            ->assertRedirect(route('guru.assessments.exams', ['teacher_subject_id' => $data['assignment']->id]))
            ->assertSessionHas('success');

        $score = ExamScore::query()->where('student_id', $data['studentA']->id)->first();
        $this->assertSame('80.00', $score->uts_score);
        $this->assertSame('92.50', $score->uas_score);
        $this->assertSame($data['user']->id, $score->updated_by);
        $this->assertDatabaseMissing('exam_scores', ['student_id' => $data['studentB']->id]);
    }

    public function test_scores_outside_zero_to_hundred_are_rejected(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->post(route('guru.assessments.exams.update', $data['assignment']), [
                'scores' => [$data['studentA']->id => ['uts' => '101', 'uas' => '-1']],
            ])
            ->assertSessionHasErrors(['scores.' . $data['studentA']->id . '.uts', 'scores.' . $data['studentA']->id . '.uas']);

        $this->assertDatabaseCount('exam_scores', 0);
    }

    public function test_teacher_cannot_write_scores_for_students_outside_the_class(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->post(route('guru.assessments.exams.update', $data['assignment']), [
                'scores' => [$data['outsider']->id => ['uts' => '90', 'uas' => '90']],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('exam_scores', 0);
    }

    public function test_teacher_cannot_save_scores_for_other_teachers_assignment(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->post(route('guru.assessments.exams.update', $data['otherAssignment']), [
                'scores' => [$data['outsider']->id => ['uts' => '90', 'uas' => '90']],
            ])
            ->assertForbidden();
    }

    public function test_template_download_lists_class_students(): void
    {
        $data = $this->scenario();

        $this->actingAs($data['user'])
            ->get(route('guru.assessments.exams.template', $data['assignment']))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_import_updates_scores_from_csv(): void
    {
        $data = $this->scenario();
        ExamScore::query()->create([
            'teacher_subject_id' => $data['assignment']->id,
            'student_id' => $data['studentB']->id,
            'uts_score' => 60,
            'uas_score' => 65,
        ]);

        $csv = "NIS,Nama Siswa,UTS,UAS\n1001,Ani,\"87,5\",90\n1002,Budi,,70\n";
        $file = UploadedFile::fake()->createWithContent('nilai.csv', $csv);

        $this->actingAs($data['user'])
            ->post(route('guru.assessments.exams.import', $data['assignment']), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $ani = ExamScore::query()->where('student_id', $data['studentA']->id)->first();
        $budi = ExamScore::query()->where('student_id', $data['studentB']->id)->first();
        $this->assertSame('87.50', $ani->uts_score);
        $this->assertSame('90.00', $ani->uas_score);
        $this->assertSame('60.00', $budi->uts_score);
        $this->assertSame('70.00', $budi->uas_score);
    }

    public function test_import_with_invalid_rows_saves_nothing(): void
    {
        $data = $this->scenario();

        $csv = "NIS,Nama Siswa,UTS,UAS\n1001,Ani,80,90\n9999,Tidak Ada,70,70\n1002,Budi,abc,70\n";
        $file = UploadedFile::fake()->createWithContent('nilai.csv', $csv);

        $this->actingAs($data['user'])
            ->post(route('guru.assessments.exams.import', $data['assignment']), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('exam_scores', 0);
    }

    public function test_students_cannot_access_exam_input(): void
    {
        $data = $this->scenario();
        $studentUser = $this->makeUser($data['studentA']->nisn, 'Ani', ['siswa']);

        $this->actingAs($studentUser)->get(route('guru.assessments.exams'))->assertForbidden();
        $this->actingAs($studentUser)
            ->post(route('guru.assessments.exams.update', $data['assignment']), ['scores' => []])
            ->assertForbidden();
    }
}
