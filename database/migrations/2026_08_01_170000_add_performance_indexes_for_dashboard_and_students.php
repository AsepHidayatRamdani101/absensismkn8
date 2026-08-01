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
        Schema::table('students', function (Blueprint $table) {
            $table->index(['classroom_id', 'nama_lengkap'], 'idx_students_classroom_name');
            $table->index('nisn', 'idx_students_nisn');
        });

        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->index('tanggal', 'idx_teacher_attendances_tanggal');
            $table->index(['teacher_id', 'tanggal'], 'idx_teacher_attendances_teacher_tanggal');
            $table->index(['classroom_id', 'tanggal'], 'idx_teacher_attendances_class_tanggal');
        });

        Schema::table('attendance_details', function (Blueprint $table) {
            $table->index('status', 'idx_attendance_details_status');
            $table->index(['student_id', 'status'], 'idx_attendance_details_student_status');
            $table->index(['teacher_attendance_id', 'status'], 'idx_attendance_details_ta_status');
        });

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->index(['teacher_id', 'academic_year_id'], 'idx_teacher_subjects_teacher_ay');
            $table->index(['classroom_id', 'academic_year_id'], 'idx_teacher_subjects_classroom_ay');
            $table->index(['subject_id', 'academic_year_id'], 'idx_teacher_subjects_subject_ay');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['teacher_subject_id', 'hari'], 'idx_schedules_teacher_subject_hari');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_teacher_subject_hari');
        });

        Schema::table('teacher_subjects', function (Blueprint $table) {
            $table->dropIndex('idx_teacher_subjects_teacher_ay');
            $table->dropIndex('idx_teacher_subjects_classroom_ay');
            $table->dropIndex('idx_teacher_subjects_subject_ay');
        });

        Schema::table('attendance_details', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_details_status');
            $table->dropIndex('idx_attendance_details_student_status');
            $table->dropIndex('idx_attendance_details_ta_status');
        });

        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_teacher_attendances_tanggal');
            $table->dropIndex('idx_teacher_attendances_teacher_tanggal');
            $table->dropIndex('idx_teacher_attendances_class_tanggal');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_classroom_name');
            $table->dropIndex('idx_students_nisn');
        });
    }
};
