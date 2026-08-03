<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mid_class_permits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->date('tanggal');
            $table->time('jam_keluar');
            $table->text('alasan');

            $table->string('foto_izin_path')->nullable();

            // wali_kelas or pengurus_kelas
            $table->enum('submitted_by_type', ['wali_kelas', 'pengurus_kelas']);

            $table->foreignId('submitted_by_teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();

            $table->foreignId('submitted_by_student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            $table->string('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mid_class_permits');
    }
};
