<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mid_class_permits', function (Blueprint $table) {
            // 'sementara' = akan kembali ke sekolah, 'penuh' = pulang sampai selesai
            $table->enum('tipe_izin', ['sementara', 'penuh'])->default('penuh')->after('jam_keluar');
            $table->time('jam_kembali')->nullable()->after('tipe_izin');
        });
    }

    public function down(): void
    {
        Schema::table('mid_class_permits', function (Blueprint $table) {
            $table->dropColumn(['tipe_izin', 'jam_kembali']);
        });
    }
};
