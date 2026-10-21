<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pjj_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions');
            $table->string('attachment_path')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->index(['teacher_id', 'created_at']);
        });

        Schema::create('pjj_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pjj_assignment_id')->constrained('pjj_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('submitted_at');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['pjj_assignment_id', 'student_id']);
            $table->index(['student_id', 'submitted_at']);
        });

        Schema::create('pjj_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('selfie_path');
            $table->timestamps();
            $table->unique(['student_id', 'attendance_date']);
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pjj_attendances');
        Schema::dropIfExists('pjj_submissions');
        Schema::dropIfExists('pjj_assignments');
    }
};
