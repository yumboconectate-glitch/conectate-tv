<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('max_connections')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('channel_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['channel_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_plan');
        Schema::dropIfExists('plans');
    }
};
