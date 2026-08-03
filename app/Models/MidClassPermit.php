<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MidClassPermit extends Model
{
    protected $table = 'mid_class_permits';

    protected $fillable = [
        'student_id',
        'tanggal',
        'jam_keluar',
        'tipe_izin',
        'jam_kembali',
        'alasan',
        'foto_izin_path',
        'submitted_by_type',
        'submitted_by_teacher_id',
        'submitted_by_student_id',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function submittedByTeacher()
    {
        return $this->belongsTo(Teacher::class, 'submitted_by_teacher_id');
    }

    public function submittedByStudent()
    {
        return $this->belongsTo(Student::class, 'submitted_by_student_id');
    }
}
