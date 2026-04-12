<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add email_verified_at (standard Laravel column)
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }

            // Add user_type (from Admin module)
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('user')->after('password');
            }

            // Add status (from Admin module)
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('user_type');
            }


            // Add tenancy_db_name if it doesn't exist
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->integer('company_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at',
                'user_type',
                'status',
                'company_id',
            ]);
        });
    }
};