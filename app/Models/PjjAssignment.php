<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjjAssignment extends Model
{
    protected $fillable = [
        'teacher_id',
        'teacher_subject_id',
        'title',
        'instructions',
        'attachment_path',
        'due_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function teacherSubject()
    {
        return $this->belongsTo(TeacherSubject::class);
    }

    public function submissions()
    {
        return $this->hasMany(PjjSubmission::class);
    }
}
