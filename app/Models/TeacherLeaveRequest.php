<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeaveRequest extends Model
{
    protected $table = 'teacher_leave_requests';

    protected $fillable = [
        'teacher_id',
        'jenis_pengajuan',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'lampiran_tugas_path',
        'deskripsi_tugas',
        'status_pengajuan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
