<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pjj_submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pjj_submission_id')->constrained('pjj_submissions')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        // Migrasi lampiran lama (satu file per submission) ke tabel baru.
        DB::table('pjj_submissions')
            ->whereNotNull('attachment_path')
            ->orderBy('id')
            ->chunkById(500, function ($submissions) {
                $now = now();
                $rows = $submissions->map(fn($submission) => [
                    'pjj_submission_id' => $submission->id,
                    'path' => $submission->attachment_path,
                    'original_name' => basename($submission->attachment_path),
                    'size' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('pjj_submission_files')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('pjj_submission_files');
    }
};
