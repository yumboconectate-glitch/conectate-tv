<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('astra_login')->unique();
            $table->string('token', 80)->unique();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('max_connections')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('astra_synced_at')->nullable();
            $table->text('astra_last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
