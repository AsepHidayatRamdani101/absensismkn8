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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('schedule_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('attendance_device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('tanggal');

            $table->time('jam_masuk')
                ->nullable();

            $table->time('jam_keluar')
                ->nullable();

            $table->enum('status', [
                'Hadir',
                'Izin',
                'Sakit',
                'Alpha',
                'Terlambat'
            ])->default('Hadir');

            $table->enum('metode', [
                'Manual',
                'RFID',
                'Face',
                'QR'
            ])->default('Manual');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
