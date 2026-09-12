<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->timestamp('expired_processed_at')->nullable()->after('expires_at');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('actor', 120)->nullable()->index();
            $table->string('ip', 64)->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn('expired_processed_at');
        });
    }
};
