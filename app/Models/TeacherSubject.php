<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSubject extends Model
{
    protected $table = 'teacher_subjects';

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'classroom_id',
        'academic_year_id',
        'grading_method',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function dailyAssessments()
    {
        return $this->hasMany(DailyAssessment::class);
    }

    public function finalGrades()
    {
        return $this->hasMany(FinalGrade::class);
    }
}
