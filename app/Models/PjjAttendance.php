<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjjAttendance extends Model
{
    protected $fillable = [
        'student_id',
        'attendance_date',
        'selfie_path',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
