<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->integer('grace_period_minutes')->default(5);
            $table->integer('early_departure_grace_minutes')->default(5);
            $table->decimal('overtime_daily_threshold_hours', 4, 2)->default(8);
            $table->decimal('overtime_weekly_threshold_hours', 5, 2)->default(40);
            $table->decimal('max_daily_overtime_hours', 4, 2)->default(4)->nullable();
            $table->decimal('overtime_multiplier', 3, 2)->default(1.5);
            $table->decimal('double_time_threshold_hours', 4, 2)->default(12)->nullable();
            $table->decimal('double_time_multiplier', 3, 2)->default(2)->nullable();
            $table->decimal('requires_break_after_hours', 4, 2)->default(5)->nullable();
            $table->integer('break_duration_minutes')->default(30)->nullable();
            $table->integer('unpaid_break_minutes')->default(0);
            $table->string('country_code')->nullable();
            $table->string('state_code')->nullable();
            $table->text('applies_to_shift_categories')->nullable();
            $table->date('effective_date');
            $table->date('expiration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('version')->default(1);
            $table->string('last_updated_by')->nullable();
            $table->datetime('last_updated_at')->nullable();
            
            			$table->index('code');
			$table->index('is_active');
			$table->index('is_default');
			$table->index('country_code');
			$table->index('effective_date');
			$table->index(['country_code', 'is_active']);
			$table->index(['is_active', 'is_default']);
			$table->index(['effective_date', 'expiration_date']);
			$table->unique(['name', 'country_code']);
			$table->unique('code');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_policies');
    }
};
