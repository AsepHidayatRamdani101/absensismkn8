<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PjjAttendance extends Model
{
    protected $fillable = [
        'student_id',
        'attendance_date',
        'selfie_path',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
