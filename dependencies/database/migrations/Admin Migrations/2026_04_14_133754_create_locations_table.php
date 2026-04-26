<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('US')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('timezone')->default('America/New_York')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_remote')->default(false);
            $table->boolean('is_headquarters')->default(false);
            $table->integer('capacity')->nullable();
            $table->text('opening_hours')->nullable();
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('geofence_radius', 6, 2)->default(100)->nullable();
            $table->string('external_id')->nullable();
            $table->datetime('last_synced_at')->nullable();
            $table->integer('employee_count')->default(0);
            $table->integer('department_count')->default(0);
            
            			$table->index('code');
			$table->index('is_active');
			$table->index('is_remote');
			$table->index('is_headquarters');
			$table->index('country');
			$table->index('timezone');
			$table->index('city');
			$table->index(['country', 'is_active']);
			$table->index(['is_remote', 'is_active']);
			$table->index(['city', 'state_province']);
			$table->unique(['name', 'city', 'country']);
			$table->unique('code');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('locations');
    }
};
