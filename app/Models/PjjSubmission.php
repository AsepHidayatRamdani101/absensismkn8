<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjjSubmission extends Model
{
    protected $fillable = [
        'pjj_assignment_id',
        'student_id',
        'answer',
        'attachment_path',
        'submitted_at',
        'score',
        'feedback',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    public function assignment()
    {
        return $this->belongsTo(PjjAssignment::class, 'pjj_assignment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function files()
    {
        return $this->hasMany(PjjSubmissionFile::class, 'pjj_submission_id');
    }
}
