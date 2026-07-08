<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = [

        'device_code',
        'device_name',
        'device_type',
        'location',
        'api_key',
        'is_active',
        'last_online'

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {

            if (!$device->api_key) {

                $device->api_key = Str::uuid();

            }

        });
    }

    public function logs()
    {
        return $this->hasMany(DeviceLog::class);
    }
}