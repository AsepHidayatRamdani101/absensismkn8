<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $table = 'teachers';

    protected $fillable = [
        'nip',
        'nuptk',
        'nama_lengkap',
        'jenis_kelamin',
        'no_hp',
        'alamat',
        'foto',
    ];
}
