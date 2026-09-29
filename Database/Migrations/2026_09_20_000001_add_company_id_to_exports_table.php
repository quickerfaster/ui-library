<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Guard: the base 'exports' table may not exist yet if migrations
        // are running in an order where this file sorts before the CREATE
        // migration (e.g. '2026_09_20' < '2026_1' in string comparison).
        if (!Schema::hasTable('exports')) {
            return;
        }

        Schema::table('exports', function (Blueprint $table) {
            if (!Schema::hasColumn('exports', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('exports')) {
            return;
        }

        Schema::table('exports', function (Blueprint $table) {
            if (Schema::hasColumn('exports', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};