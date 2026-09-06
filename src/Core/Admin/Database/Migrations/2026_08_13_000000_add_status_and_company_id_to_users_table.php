<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the columns required by the admin.user data config and the
 * "Users by Status" dashboard widget to the users table.
 *
 * The consuming application's base users migration may not include these
 * columns (e.g. the stock Laravel `0001_01_01_000000_create_users_table`
 * migration). The QuickerFaster UI library ships this idempotent migration
 * so every consuming app ends up with the same schema.
 *
 * Columns added:
 *   - status     (string, default 'active')
 *   - company_id (nullable unsigned big integer)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active');
            }

            if (! Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }
        });

        // Add lookup indexes (guarded against duplicates).
        if (Schema::hasColumn('users', 'status') && ! Schema::hasIndex('users', 'users_status_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('status');
            });
        }

        if (Schema::hasColumn('users', 'company_id') && ! Schema::hasIndex('users', 'users_company_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('company_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropColumn('company_id');
            }

            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
