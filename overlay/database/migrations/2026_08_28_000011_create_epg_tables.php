<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epg_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('url', 1000);
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedSmallInteger('refresh_hours')->default(6);
            $table->timestamp('last_import_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('last_channel_count')->default(0);
            $table->unsignedInteger('last_programme_count')->default(0);
            $table->timestamps();
        });

        Schema::create('epg_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epg_source_id')->constrained()->cascadeOnDelete();
            $table->string('xmltv_id', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('normalized_name', 255)->nullable()->index();
            $table->string('icon_url', 1000)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['epg_source_id', 'xmltv_id']);
        });

        Schema::create('epg_programmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epg_source_id')->constrained()->cascadeOnDelete();
            $table->string('xmltv_id', 255)->index();
            $table->timestampTz('start_at')->index();
            $table->timestampTz('stop_at')->index();
            $table->string('title', 500);
            $table->string('subtitle', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('category', 255)->nullable();
            $table->string('icon_url', 1000)->nullable();
            $table->timestamps();
            $table->unique(['epg_source_id', 'xmltv_id', 'start_at', 'stop_at'], 'epg_programmes_unique_slot');
            $table->index(['epg_source_id', 'xmltv_id', 'start_at'], 'epg_programmes_lookup');
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->foreignId('epg_source_id')->nullable()->after('published')
                ->constrained('epg_sources')->nullOnDelete();
            $table->string('epg_xmltv_id', 255)->nullable()->after('epg_source_id')->index();
            $table->timestamp('epg_auto_mapped_at')->nullable()->after('epg_xmltv_id');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropForeign(['epg_source_id']);
            $table->dropColumn(['epg_source_id', 'epg_xmltv_id', 'epg_auto_mapped_at']);
        });
        Schema::dropIfExists('epg_programmes');
        Schema::dropIfExists('epg_channels');
        Schema::dropIfExists('epg_sources');
    }
};
