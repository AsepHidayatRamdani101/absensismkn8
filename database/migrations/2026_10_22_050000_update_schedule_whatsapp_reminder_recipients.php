<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE schedule_whatsapp_reminders MODIFY recipient_type ENUM('teacher', 'parent', 'student') NOT NULL");
        DB::table('schedule_whatsapp_reminders')
            ->where('recipient_type', 'parent')
            ->delete();
        DB::statement("ALTER TABLE schedule_whatsapp_reminders MODIFY recipient_type ENUM('teacher', 'student') NOT NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE schedule_whatsapp_reminders MODIFY recipient_type ENUM('teacher', 'parent', 'student') NOT NULL");
        DB::statement("ALTER TABLE schedule_whatsapp_reminders MODIFY recipient_type ENUM('teacher', 'parent') NOT NULL");
    }
};
