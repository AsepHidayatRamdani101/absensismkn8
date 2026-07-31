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
        Schema::create('officer_attendance_permits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('officer_student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('classroom_id')
                ->constrained('classrooms')
                ->cascadeOnDelete();

            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->date('request_date');
            $table->text('alasan');

            $table->enum('status_pengajuan', ['Menunggu', 'Disetujui', 'Ditolak'])
                ->default('Menunggu');

            $table->text('catatan_kurikulum')->nullable();

            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['officer_student_id', 'schedule_id', 'request_date'], 'officer_permit_unique_daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_attendance_permits');
    }
};
