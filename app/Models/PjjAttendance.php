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
        'check_in_at',
        'check_out_at',
        'check_out_selfie_path',
        'check_out_latitude',
        'check_out_longitude',
        'status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'check_out_latitude' => 'decimal:7',
        'check_out_longitude' => 'decimal:7',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
