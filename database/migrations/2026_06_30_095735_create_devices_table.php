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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->string('device_code')->unique();
            $table->string('device_name');

            $table->enum('device_type', ['RFID', 'FACE', 'QR']);

            $table->string('location')->nullable();

            $table->string('api_key')->unique();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_online')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
