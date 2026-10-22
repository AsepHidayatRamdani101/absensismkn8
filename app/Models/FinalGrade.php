<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalGrade extends Model
{
    protected $fillable = ['teacher_subject_id', 'student_id', 'score', 'note'];

    protected $casts = ['score' => 'decimal:2'];

    public function teacherSubject()
    {
        return $this->belongsTo(TeacherSubject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
