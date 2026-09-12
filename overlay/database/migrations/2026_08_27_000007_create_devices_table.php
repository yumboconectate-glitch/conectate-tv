<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('device_key', 64);
            $table->string('device_name')->default('Dispositivo IPTV');
            $table->string('client_ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('last_channel_id')->nullable()->constrained('channels')->nullOnDelete();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->unsignedBigInteger('request_count')->default(0);
            $table->timestamps();
            $table->unique(['subscriber_id', 'device_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
