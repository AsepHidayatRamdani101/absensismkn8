<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pjj_attendances', function (Blueprint $table) {
            $table->string('status', 20)->default('Hadir')->after('check_out_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('pjj_attendances', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
