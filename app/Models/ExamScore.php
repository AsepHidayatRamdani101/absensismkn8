<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamScore extends Model
{
    protected $fillable = [
        'teacher_subject_id',
        'student_id',
        'uts_score',
        'uas_score',
        'updated_by',
    ];

    protected $casts = [
        'uts_score' => 'decimal:2',
        'uas_score' => 'decimal:2',
    ];

    public function teacherSubject()
    {
        return $this->belongsTo(TeacherSubject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
