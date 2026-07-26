<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nama_orang_tua_wali')->nullable()->after('nama_lengkap');
            $table->string('no_hp_orang_tua')->nullable()->after('no_hp');
            $table->decimal('tinggi_badan', 5, 2)->nullable()->after('no_hp_orang_tua');
            $table->decimal('berat_badan', 5, 2)->nullable()->after('tinggi_badan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'nama_orang_tua_wali',
                'no_hp_orang_tua',
                'tinggi_badan',
                'berat_badan',
            ]);
        });
    }
};