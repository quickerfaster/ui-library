<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('shifts', 'id')->onDelete('restrict');
            $table->date('schedule_date');
            $table->string('status')->default('scheduled');
            $table->time('start_time_override')->nullable();
            $table->time('end_time_override')->nullable();
            $table->datetime('actual_start_time')->nullable();
            $table->datetime('actual_end_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->boolean('is_cover_required')->default(false);
            $table->foreignId('cover_employee_id')->nullable()->constrained('employees', 'id')->onDelete('set null');
            $table->foreignId('attendance_id')->nullable()->constrained('attendances', 'id')->onDelete('set null');
            $table->string('schedule_type')->default('regular')->nullable();
            $table->boolean('is_published')->default(true);
            $table->datetime('published_at')->nullable();
            $table->boolean('created_from_template')->default(false);
            $table->string('last_modified_by')->nullable();
            $table->datetime('last_modified_at')->nullable();
            
            			$table->index('employee_id');
			$table->index('shift_id');
			$table->index('schedule_date');
			$table->index('status');
			$table->index('is_published');
			$table->index('is_cover_required');
			$table->index(['employee_id', 'schedule_date']);
			$table->index(['shift_id', 'schedule_date']);
			$table->index(['status', 'schedule_date']);
			$table->index(['is_published', 'schedule_date']);
			$table->unique(['employee_id', 'schedule_date']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_schedules');
    }
};
