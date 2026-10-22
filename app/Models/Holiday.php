<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'tanggal',
        'tanggal_akhir',
        'jam_mulai_libur',
        'keterangan',
        'is_national',
        'is_pjj',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_akhir' => 'date',
        'is_national' => 'boolean',
        'is_pjj' => 'boolean',
    ];
}
