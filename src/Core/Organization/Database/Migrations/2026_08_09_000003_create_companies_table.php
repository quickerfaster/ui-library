<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 50)->nullable()->unique();
                $table->string('subdomain', 100)->nullable()->unique();
                $table->string('logo', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('website', 255)->nullable();
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('tax_id', 100)->nullable();
                $table->string('registration_number', 100)->nullable();
                $table->string('currency', 3)->nullable()->default('USD');
                $table->string('timezone', 50)->nullable()->default('UTC');
                $table->string('date_format', 20)->nullable()->default('Y-m-d');
                $table->boolean('is_active')->default(true);
                $table->string('status', 50)->default('active');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};