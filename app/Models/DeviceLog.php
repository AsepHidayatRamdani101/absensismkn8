<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    protected $fillable = [

        'device_id',
        'action',
        'message',
        'payload',
        'ip_address'

    ];

    protected $casts = [

        'payload' => 'array'

    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}