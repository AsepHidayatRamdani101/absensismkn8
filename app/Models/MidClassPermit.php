<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'scan_token',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $permit): void {
            $permit->scan_token ??= (string) Str::uuid();
        });
    }

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
