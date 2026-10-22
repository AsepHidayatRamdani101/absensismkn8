<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAssessmentScore extends Model
{
    protected $fillable = ['daily_assessment_id', 'student_id', 'score', 'note'];

    protected $casts = ['score' => 'decimal:2'];

    public function assessment()
    {
        return $this->belongsTo(DailyAssessment::class, 'daily_assessment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
