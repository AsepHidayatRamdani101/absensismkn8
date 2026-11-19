<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $table = 'school_settings';

    protected $fillable = [
        'nama_sekolah',
        'npsn',
        'alamat',
        'telepon',
        'email',
        'logo',
        'jam_masuk',
        'batas_terlambat',
        'pkl_mode_active',
        'monthly_achievement_duration_days',
        'report_daily_weight',
        'report_uts_weight',
        'report_uas_weight',
        'report_kkm',
        'report_city',
        'principal_name',
        'principal_nip',
    ];

    protected $casts = [
        'report_daily_weight' => 'integer',
        'report_uts_weight' => 'integer',
        'report_uas_weight' => 'integer',
        'report_kkm' => 'integer',
    ];
}
