<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_whatsapp_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('recipient_type', ['teacher', 'parent']);
            $table->unsignedBigInteger('recipient_id');
            $table->timestamps();
            $table->unique(
                ['schedule_id', 'tanggal', 'recipient_type', 'recipient_id'],
                'schedule_whatsapp_reminders_unique_recipient'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_whatsapp_reminders');
    }
};
