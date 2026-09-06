<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_definitions', function (Blueprint $table) {
            $table->json('notifications')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_definitions', function (Blueprint $table) {
            $table->dropColumn('notifications');
        });
    }
};
