<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code')->nullable();
            $table->string('region')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('applicable_to')->default('all_employees');
            $table->string('year')->default(2026)->nullable();
            $table->integer('holiday_count')->default(0);
            $table->datetime('last_updated')->nullable();
            
            			$table->index('name');
			$table->index('country_code');
			$table->index('year');
			$table->index('is_active');
			$table->index('is_default');
			$table->index('applicable_to');
			$table->index(['country_code', 'year']);
			$table->index(['is_active', 'is_default']);
			$table->index(['year', 'applicable_to']);
			$table->unique(['name', 'year']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('holiday_calendars');
    }
};
