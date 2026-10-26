<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pjj_attendances', function (Blueprint $table) {
            $table->timestamp('check_in_at')->nullable()->after('longitude');
            $table->timestamp('check_out_at')->nullable()->after('check_in_at');
            $table->string('check_out_selfie_path')->nullable()->after('check_out_at');
            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_out_selfie_path');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
        });

        DB::table('pjj_attendances')
            ->whereNull('check_in_at')
            ->update(['check_in_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('pjj_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_at',
                'check_out_at',
                'check_out_selfie_path',
                'check_out_latitude',
                'check_out_longitude',
            ]);
        });
    }
};
