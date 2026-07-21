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
        Schema::create('student_leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->enum('jenis_pengajuan', ['Izin', 'Sakit']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('alasan');
            $table->string('foto_surat_path')->nullable();

            $table->enum('status_pengajuan', ['Menunggu', 'Disetujui', 'Ditolak'])
                ->default('Menunggu');

            $table->text('catatan_wali')->nullable();

            $table->foreignId('verified_by_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_leave_requests');
    }
};
