<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'classroom_id',
        'homeroom_note',
        'sick_days',
        'permit_days',
        'absent_days',
        'updated_by',
    ];

    protected $casts = [
        'sick_days' => 'integer',
        'permit_days' => 'integer',
        'absent_days' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
