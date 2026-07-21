<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    public const JABATAN_GURU = 'guru';
    public const JABATAN_KEPALA_PROGRAM = 'kepala_program';
    public const JABATAN_KEPALA_SEKOLAH = 'kepala_sekolah';
    public const JABATAN_BK = 'bk';

    public const JABATAN_LABELS = [
        self::JABATAN_GURU => 'Guru',
        self::JABATAN_KEPALA_PROGRAM => 'Kepala Program',
        self::JABATAN_KEPALA_SEKOLAH => 'Kepala Sekolah',
        self::JABATAN_BK => 'BK',
    ];

    protected $table = 'teachers';

    protected $fillable = [
        'nip',
        'nuptk',
        'nama_lengkap',
        'jabatan',
        'jenis_kelamin',
        'no_hp',
        'alamat',
        'is_wali_kelas',
        'wali_classroom_id',
        'is_kurikulum',
        'foto',
    ];

    protected $casts = [
        'is_wali_kelas' => 'boolean',
        'is_kurikulum' => 'boolean',
    ];

    public function waliClassroom()
    {
        return $this->belongsTo(Classroom::class, 'wali_classroom_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(TeacherLeaveRequest::class);
    }

    public function verifiedStudentLeaveRequests()
    {
        return $this->hasMany(StudentLeaveRequest::class, 'verified_by_teacher_id');
    }

    public function getJabatanLabelAttribute(): string
    {
        return self::JABATAN_LABELS[$this->jabatan] ?? ucfirst(str_replace('_', ' ', (string) $this->jabatan));
    }
}
