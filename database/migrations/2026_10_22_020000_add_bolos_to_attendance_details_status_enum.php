<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Dispen','Alpha','Bolos','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }

    public function down(): void
    {
        DB::table('attendance_details')->where('status', 'Bolos')->update(['status' => 'Alpha']);
        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Dispen','Alpha','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }
};
