<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAssessment extends Model
{
    protected $fillable = [
        'teacher_subject_id',
        'title',
        'type',
        'assessment_date',
        'max_score',
        'weight',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function teacherSubject()
    {
        return $this->belongsTo(TeacherSubject::class);
    }

    public function scores()
    {
        return $this->hasMany(DailyAssessmentScore::class);
    }
}
