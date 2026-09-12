<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_events', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 191)->unique();
            $table->string('type', 80)->index();
            $table->string('severity', 20)->default('warning')->index();
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('entity_type', 80)->nullable()->index();
            $table->string('entity_id', 100)->nullable()->index();
            $table->json('context')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('first_seen_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('acknowledged_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_events');
    }
};
