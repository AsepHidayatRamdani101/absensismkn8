<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reward_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('semester', 20);
            $table->date('transaction_date');
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('reward_category_id')->constrained('reward_categories')->restrictOnDelete();
            $table->foreignId('reward_item_id')->constrained('reward_items')->restrictOnDelete();
            $table->integer('point');
            $table->decimal('weight_total', 10, 2)->default(0);
            $table->decimal('weighted_point', 12, 2)->default(0);
            $table->json('dimension_payload')->nullable();
            $table->string('source', 80);
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('actor_role', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['draft', 'pending', 'validated', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['academic_year_id', 'semester', 'transaction_date'], 'reward_tx_period_idx');
            $table->index(['student_id', 'transaction_date'], 'reward_tx_student_date_idx');
            $table->index(['status', 'transaction_date'], 'reward_tx_status_date_idx');
        });

        Schema::create('violation_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('semester', 20);
            $table->date('transaction_date');
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->restrictOnDelete();
            $table->foreignId('violation_category_id')->constrained('violation_categories')->restrictOnDelete();
            $table->foreignId('violation_item_id')->constrained('violation_items')->restrictOnDelete();
            $table->integer('point');
            $table->decimal('weight_total', 10, 2)->default(0);
            $table->decimal('weighted_point', 12, 2)->default(0);
            $table->json('dimension_payload')->nullable();
            $table->string('source', 80);
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('actor_role', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('evidence_path')->nullable();
            $table->enum('status', ['draft', 'pending', 'validated', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['academic_year_id', 'semester', 'transaction_date'], 'violation_tx_period_idx');
            $table->index(['student_id', 'transaction_date'], 'violation_tx_student_date_idx');
            $table->index(['status', 'transaction_date'], 'violation_tx_status_date_idx');
        });

        Schema::create('student_character_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('character_dimension_id')->constrained('character_dimensions')->cascadeOnDelete();
            $table->decimal('reward_score_total', 12, 2)->default(0);
            $table->decimal('violation_score_total', 12, 2)->default(0);
            $table->decimal('score_total', 12, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'character_dimension_id'], 'student_dimension_unique');
            $table->index(['student_id', 'score_total'], 'student_score_total_idx');
        });

        Schema::create('student_character_statistics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('semester', 20);
            $table->unsignedInteger('reward_count')->default(0);
            $table->unsignedInteger('violation_count')->default(0);
            $table->decimal('reward_weighted_total', 12, 2)->default(0);
            $table->decimal('violation_weighted_total', 12, 2)->default(0);
            $table->decimal('character_score_total', 12, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'semester'], 'student_character_stats_unique');
            $table->index(['academic_year_id', 'semester', 'character_score_total'], 'student_character_stats_rank_idx');
        });

        Schema::create('student_warning_letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('semester', 20);
            $table->string('sp_level', 20);
            $table->decimal('violation_weighted_total', 12, 2)->default(0);
            $table->boolean('is_manual_override')->default(false);
            $table->string('status', 30)->default('active');
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'academic_year_id', 'semester', 'status'], 'student_sp_status_idx');
        });

        Schema::create('pancawaluya_transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('semester', 20)->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('action', 40);
            $table->string('status', 30)->nullable();
            $table->decimal('score_before', 12, 2)->nullable();
            $table->decimal('score_after', 12, 2)->nullable();
            $table->json('payload_before')->nullable();
            $table->json('payload_after')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 80)->nullable();
            $table->string('source', 80)->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'panca_history_ref_idx');
            $table->index(['student_id', 'transaction_date'], 'panca_history_student_date_idx');
            $table->index(['action', 'created_at'], 'panca_history_action_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pancawaluya_transaction_histories');
        Schema::dropIfExists('student_warning_letters');
        Schema::dropIfExists('student_character_statistics');
        Schema::dropIfExists('student_character_scores');
        Schema::dropIfExists('violation_transactions');
        Schema::dropIfExists('reward_transactions');
    }
};
