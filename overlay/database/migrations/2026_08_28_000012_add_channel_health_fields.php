<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->timestamp('offline_since')->nullable()->after('on_air')->index();
            $table->timestamp('last_on_air_at')->nullable()->after('offline_since');
            $table->timestamp('health_changed_at')->nullable()->after('last_on_air_at');
            $table->unsignedInteger('outage_count')->default(0)->after('health_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['offline_since','last_on_air_at','health_changed_at','outage_count']);
        });
    }
};
