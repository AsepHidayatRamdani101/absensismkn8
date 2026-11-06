<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mid_class_permits', function (Blueprint $table) {
            $table->uuid('scan_token')->nullable()->unique()->after('catatan');
        });

        foreach (\DB::table('mid_class_permits')->whereNull('scan_token')->pluck('id') as $id) {
            \DB::table('mid_class_permits')->where('id', $id)->update(['scan_token' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('mid_class_permits', function (Blueprint $table) {
            $table->dropUnique(['scan_token']);
            $table->dropColumn('scan_token');
        });
    }
};
