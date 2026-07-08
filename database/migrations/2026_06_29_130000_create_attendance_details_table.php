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
        Schema::create('attendance_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_attendance_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', [
                'Hadir',
                'Izin',
                'Sakit',
                'Alpha',
                'Terlambat'
            ])->default('Hadir');

            $table->string('keterangan')
                ->nullable();

            $table->time('jam_absen')
                ->nullable();

            $table->timestamps();

            $table->unique(['teacher_attendance_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_details');
    }
};
