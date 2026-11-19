<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('attendance_details', function (Blueprint $table) {
                $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Dispen', 'Alpha', 'Terlambat'])->default('Hadir')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Dispen','Alpha','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('attendance_details', function (Blueprint $table) {
                $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Terlambat'])->default('Hadir')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Alpha','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }
};
