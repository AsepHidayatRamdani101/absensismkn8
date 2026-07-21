<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->enum('kehadiran_guru', ['Hadir', 'Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Home Visit'])
                ->default('Hadir')
                ->after('catatan_guru');

            $table->string('tugas_file_path')
                ->nullable()
                ->after('kehadiran_guru');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropColumn(['kehadiran_guru', 'tugas_file_path']);
        });
    }
};
