<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE student_leave_requests MODIFY jenis_pengajuan ENUM('Izin', 'Sakit', 'Dispen') NOT NULL");
    }

    public function down(): void
    {
        DB::table('student_leave_requests')->where('jenis_pengajuan', 'Dispen')->update(['jenis_pengajuan' => 'Izin']);
        DB::statement("ALTER TABLE student_leave_requests MODIFY jenis_pengajuan ENUM('Izin', 'Sakit') NOT NULL");
    }
};
