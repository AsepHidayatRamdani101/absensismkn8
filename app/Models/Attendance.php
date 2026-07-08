<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'student_id',
        'schedule_id',
        'attendance_device_id',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'status',
        'metode',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendanceDevice()
    {
        return $this->belongsTo(AttendanceDevice::class);
    }
}
