<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('report_daily_weight')->default(50);
            $table->unsignedTinyInteger('report_uts_weight')->default(25);
            $table->unsignedTinyInteger('report_uas_weight')->default(25);
            $table->unsignedTinyInteger('report_kkm')->default(75);
            $table->string('report_city', 100)->nullable();
            $table->string('principal_name')->nullable();
            $table->string('principal_nip', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'report_daily_weight',
                'report_uts_weight',
                'report_uas_weight',
                'report_kkm',
                'report_city',
                'principal_name',
                'principal_nip',
            ]);
        });
    }
};
