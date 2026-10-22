<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->enum('grading_method', ['manual', 'automatic'])->default('manual')->after('academic_year_id');
        });

        Schema::create('daily_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['project', 'task']);
            $table->date('assessment_date');
            $table->decimal('max_score', 5, 2)->default(100);
            $table->decimal('weight', 6, 2)->default(1);
            $table->timestamps();
            $table->index(['teacher_subject_id', 'assessment_date']);
        });

        Schema::create('daily_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['daily_assessment_id', 'student_id']);
        });

        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['teacher_subject_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
        Schema::dropIfExists('daily_assessment_scores');
        Schema::dropIfExists('daily_assessments');
        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropColumn('grading_method');
        });
    }
};
