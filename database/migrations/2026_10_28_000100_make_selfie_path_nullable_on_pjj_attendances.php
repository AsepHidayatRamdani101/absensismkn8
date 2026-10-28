<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selfie boleh kosong untuk absensi PJJ yang diinput paksa oleh admin
     * (siswa terkendala perangkat/jaringan saat PJJ). Kolom forced_* menandai
     * baris hasil force absensi admin.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE pjj_attendances MODIFY selfie_path VARCHAR(255) NULL');

        Schema::table('pjj_attendances', function (Blueprint $table) {
            $table->timestamp('forced_at')->nullable()->after('status');
            $table->string('forced_note')->nullable()->after('forced_at');
        });
    }

    public function down(): void
    {
        Schema::table('pjj_attendances', function (Blueprint $table) {
            $table->dropColumn(['forced_at', 'forced_note']);
        });

        DB::table('pjj_attendances')->whereNull('selfie_path')->update(['selfie_path' => '']);
        DB::statement('ALTER TABLE pjj_attendances MODIFY selfie_path VARCHAR(255) NOT NULL');
    }
};
