<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pjj_assignments', function (Blueprint $table) {
            $table->string('task_type', 30)->default('essay')->after('teacher_subject_id');
            $table->json('questions')->nullable()->after('instructions');
        });

        Schema::table('pjj_submissions', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('pjj_assignments', function (Blueprint $table) {
            $table->dropColumn(['task_type', 'questions']);
        });

        Schema::table('pjj_submissions', function (Blueprint $table) {
            $table->dropColumn('answers');
        });
    }
};
