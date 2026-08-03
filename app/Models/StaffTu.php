<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffTu extends Model
{
    protected $table = 'staff_tu';

    public const JABATAN_KEPALA_TU = 'kepala_tu';
    public const JABATAN_STAF_TU   = 'staf_tu';

    public const JABATAN_LABELS = [
        self::JABATAN_KEPALA_TU => 'Kepala TU',
        self::JABATAN_STAF_TU   => 'Staf TU',
    ];

    protected $fillable = [
        'nip',
        'nama_lengkap',
        'jabatan',
        'jenis_kelamin',
        'no_hp',
        'alamat',
        'foto',
    ];

    public function getJabatanLabelAttribute(): string
    {
        return self::JABATAN_LABELS[$this->jabatan] ?? ucfirst(str_replace('_', ' ', (string) $this->jabatan));
    }
}
