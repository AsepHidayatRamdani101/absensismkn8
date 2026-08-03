<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Dispen','Alpha','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_details MODIFY COLUMN status ENUM('Hadir','Izin','Sakit','Alpha','Terlambat') NOT NULL DEFAULT 'Hadir'");
    }
};
