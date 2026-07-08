<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'tanggal',
        'keterangan',
        'is_national',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'is_national' => 'boolean',
    ];
}
