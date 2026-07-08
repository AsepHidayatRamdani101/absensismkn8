<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDetail extends Model
{
    protected $table = 'attendance_details';

    protected $fillable = [
        'teacher_attendance_id',
        'student_id',
        'status',
        'keterangan',
        'jam_absen',
    ];

    public function teacherAttendance()
    {
        return $this->belongsTo(TeacherAttendance::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
