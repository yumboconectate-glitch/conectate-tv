<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('astra_login');
            $table->text('access_password')->nullable()->after('username');
            $table->timestamp('astra_detached_at')->nullable()->after('astra_last_error');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'access_password', 'astra_detached_at']);
        });
    }
};
