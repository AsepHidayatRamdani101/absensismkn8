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
            Schema::table('attendance_details', function (Blueprint $table) {
                $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Dispen', 'Alpha', 'Bolos', 'Terlambat'])->default('Hadir')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Dispen','Alpha','Bolos','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }

    public function down(): void
    {
        DB::table('attendance_details')->where('status', 'Bolos')->update(['status' => 'Alpha']);

        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('attendance_details', function (Blueprint $table) {
                $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Dispen', 'Alpha', 'Terlambat'])->default('Hadir')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Dispen','Alpha','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }
};
