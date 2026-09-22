<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};