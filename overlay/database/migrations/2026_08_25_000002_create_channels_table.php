<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('astra_server_id')->constrained()->cascadeOnDelete();
            $table->string('astra_stream_id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('input_count')->default(0);
            $table->unsignedSmallInteger('output_count')->default(0);
            $table->boolean('on_air')->nullable();
            $table->unsignedBigInteger('bitrate')->nullable();
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedBigInteger('cc_errors')->default(0);
            $table->unsignedBigInteger('pes_errors')->default(0);
            $table->unsignedBigInteger('scrambling_errors')->default(0);
            $table->jsonb('status_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['astra_server_id', 'astra_stream_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
