<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLeaveRequest extends Model
{
    protected $table = 'student_leave_requests';

    protected $fillable = [
        'student_id',
        'jenis_pengajuan',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'foto_surat_path',
        'status_pengajuan',
        'catatan_wali',
        'verified_by_teacher_id',
        'verified_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'verified_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function verifier()
    {
        return $this->belongsTo(Teacher::class, 'verified_by_teacher_id');
    }
}
