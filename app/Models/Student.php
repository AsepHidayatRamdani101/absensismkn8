<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public const JABATAN_KETUA_KELAS = 'ketua_kelas';
    public const JABATAN_SEKRETARIS = 'sekretaris';
    public const JABATAN_BENDAHARA = 'bendahara';

    public const ALLOWED_JABATAN_FOR_TEACHER_ATTENDANCE = [
        self::JABATAN_KETUA_KELAS,
        self::JABATAN_SEKRETARIS,
        self::JABATAN_BENDAHARA,
    ];

    public const JABATAN_LABELS = [
        self::JABATAN_KETUA_KELAS => 'KM',
        self::JABATAN_SEKRETARIS => 'Sekretaris',
        self::JABATAN_BENDAHARA => 'Bendahara',
    ];

    protected $fillable = [
        'nis',
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'classroom_id',
        'jabatan_kelas',
        'alamat',
        'no_hp',
        'foto',
    ];

    public function canSubmitTeacherAttendance(): bool
    {
        return in_array($this->jabatan_kelas, self::ALLOWED_JABATAN_FOR_TEACHER_ATTENDANCE, true);
    }

    public function getJabatanKelasLabelAttribute(): string
    {
        return self::JABATAN_LABELS[$this->jabatan_kelas] ?? '-';
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceDetails()
    {
        return $this->hasMany(AttendanceDetail::class);
    }
}
