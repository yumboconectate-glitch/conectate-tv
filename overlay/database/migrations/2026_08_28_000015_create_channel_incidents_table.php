<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('kind', 40)->default('offline')->index();
            $table->string('severity', 20)->default('warning')->index();
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->unsignedBigInteger('duration_seconds')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'kind', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_incidents');
    }
};
