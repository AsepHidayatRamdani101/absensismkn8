<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    protected $fillable = [

        'kode_jurusan',
        'nama_jurusan',
        'singkatan',

    ];

    public function classrooms()
    {
        return $this->hasMany(
            Classroom::class
        );
    }
}