<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficerAttendancePermit extends Model
{
    protected $table = 'officer_attendance_permits';

    protected $fillable = [
        'officer_student_id',
        'classroom_id',
        'schedule_id',
        'teacher_id',
        'request_date',
        'alasan',
        'status_pengajuan',
        'catatan_kurikulum',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function officer()
    {
        return $this->belongsTo(Student::class, 'officer_student_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
