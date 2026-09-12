<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        if (!Schema::hasColumn('channels', 'epg_kind')) {

            Schema::table('channels', function (Blueprint $table) {

                $table->string('epg_kind', 20)

                    ->nullable()

                    ->after('epg_auto_mapped_at')

                    ->index();

            });

        }

    }



    public function down(): void

    {

        if (Schema::hasColumn('channels', 'epg_kind')) {

            Schema::table('channels', function (Blueprint $table) {

                $table->dropColumn('epg_kind');

            });

        }

    }

};
