<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('channel_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->unsignedInteger('channel_number')->nullable()->after('display_name')->index();
            $table->string('logo_url', 500)->nullable()->after('channel_number');
            $table->foreignId('category_id')->nullable()->after('logo_url')
                ->constrained('channel_categories')->nullOnDelete();
            $table->boolean('published')->default(true)->after('category_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'display_name', 'channel_number', 'logo_url',
                'category_id', 'published',
            ]);
        });

        Schema::dropIfExists('channel_categories');
    }
};
