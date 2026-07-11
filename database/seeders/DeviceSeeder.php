<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Device;
use Illuminate\Support\Str;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Device::firstOrCreate(
            ['device_code' => 'RFID-001'],

            [
                'device_name' => 'Gerbang Utama',
                'device_type' => 'RFID',
                'location' => 'Pintu Gerbang',
                'api_key' => (string) Str::uuid(),
            ],
        );

        Device::firstOrCreate(
            ['device_code' => 'FACE-001'],

            [
                'device_name' => 'Ruang TU',
                'device_type' => 'FACE',
                'location' => 'Lobby Sekolah',
                'api_key' => (string) Str::uuid(),
            ],
        );
    }
}
