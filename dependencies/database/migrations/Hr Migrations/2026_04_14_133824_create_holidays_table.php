<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained('holiday_calendars', 'id')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('date');
            $table->date('observed_date')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->string('recurrence_pattern')->default('yearly_fixed')->nullable();
            $table->text('recurrence_rule')->nullable();
            $table->string('holiday_type')->default('company');
            $table->boolean('is_paid_holiday')->default(true);
            $table->boolean('affects_payroll')->default(true);
            $table->string('business_impact')->default('office_closed');
            $table->text('eligible_employee_types');
            $table->decimal('holiday_pay_rate', 4, 2)->default(1)->nullable();
            $table->decimal('minimum_hours_for_pay', 4, 2)->default(8)->nullable();
            $table->string('country_code')->nullable();
            $table->string('region_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_half_day')->default(false);
            $table->time('half_day_end_time')->nullable();
            $table->integer('year')->nullable();
            $table->boolean('generated_from_template')->default(false);
            $table->integer('override_id')->nullable();
            $table->datetime('last_synced_at')->nullable();
            
            			$table->index('calendar_id');
			$table->index('date');
			$table->index('is_active');
			$table->index('is_paid_holiday');
			$table->index('holiday_type');
			$table->index('business_impact');
			$table->index('country_code');
			$table->index(['calendar_id', 'date']);
			$table->index(['date', 'is_active']);
			$table->index(['country_code', 'region_code']);
			$table->index(['is_recurring', 'date']);
			$table->unique(['calendar_id', 'date', 'name']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
