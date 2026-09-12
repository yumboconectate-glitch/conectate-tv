<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('astra_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scheme')->default('http');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(8000);
            $table->string('username');
            $table->text('password');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->jsonb('last_system_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('astra_servers');
    }
};
