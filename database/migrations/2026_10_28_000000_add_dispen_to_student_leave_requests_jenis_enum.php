<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('student_leave_requests', function (Blueprint $table) {
                $table->enum('jenis_pengajuan', ['Izin', 'Sakit', 'Dispen'])->change();
            });

            return;
        }

        DB::statement("ALTER TABLE student_leave_requests MODIFY jenis_pengajuan ENUM('Izin', 'Sakit', 'Dispen') NOT NULL");
    }

    public function down(): void
    {
        DB::table('student_leave_requests')->where('jenis_pengajuan', 'Dispen')->update(['jenis_pengajuan' => 'Izin']);

        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('student_leave_requests', function (Blueprint $table) {
                $table->enum('jenis_pengajuan', ['Izin', 'Sakit'])->change();
            });

            return;
        }

        DB::statement("ALTER TABLE student_leave_requests MODIFY jenis_pengajuan ENUM('Izin', 'Sakit') NOT NULL");
    }
};
