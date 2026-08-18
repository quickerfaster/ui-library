<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->string('type', 50)->default('office');
                $table->text('address')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state_code', 100)->nullable();
                $table->string('country_code', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('timezone', 50)->nullable();
                $table->boolean('is_headquarters')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->string('address_line_1')->nullable();
                $table->string('address_line_2')->nullable();
                $table->string('website')->nullable();
                $table->boolean('is_remote')->default(false);
                $table->integer('capacity')->nullable();
                $table->text('opening_hours')->nullable();
                $table->date('opening_date')->nullable();
                $table->date('closing_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};