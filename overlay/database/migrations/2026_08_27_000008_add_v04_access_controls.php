<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_devices')->default(5)->after('max_connections');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('custom_name', 100)->nullable()->after('device_name');
            $table->boolean('blocked')->default(false)->after('custom_name')->index();
            $table->boolean('ip_blocked')->default(false)->after('blocked')->index();
            $table->timestamp('blocked_at')->nullable()->after('ip_blocked');
            $table->text('block_reason')->nullable()->after('blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'custom_name', 'blocked', 'ip_blocked', 'blocked_at', 'block_reason',
            ]);
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn('max_devices');
        });
    }
};
