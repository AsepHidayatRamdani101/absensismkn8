<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_whatsapp_reminders', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('recipient_id');
            $table->string('classroom_name')->nullable()->after('recipient_name');
            $table->string('subject_name')->nullable()->after('classroom_name');
            $table->time('schedule_start')->nullable()->after('subject_name');
            $table->time('schedule_end')->nullable()->after('schedule_start');
            $table->timestamp('sent_at')->nullable()->after('schedule_end')->index();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_whatsapp_reminders', function (Blueprint $table) {
            $table->dropIndex(['sent_at']);
            $table->dropColumn([
                'recipient_name',
                'classroom_name',
                'subject_name',
                'schedule_start',
                'schedule_end',
                'sent_at',
            ]);
        });
    }
};
