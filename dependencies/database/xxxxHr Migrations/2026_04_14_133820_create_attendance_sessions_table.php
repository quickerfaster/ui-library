<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances', 'id')->onDelete('cascade');
            $table->foreignId('clock_in_event_id')->nullable()->constrained('clock_events', 'id')->onDelete('set null');
            $table->foreignId('clock_out_event_id')->nullable()->constrained('clock_events', 'id')->onDelete('set null');
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->decimal('duration_hours', 6, 2)->default(0);
            $table->string('session_type')->default('work')->nullable();
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_adjusted')->default(false);
            $table->text('adjustment_reason')->nullable();
            $table->string('adjusted_by')->nullable();
            $table->datetime('adjusted_at')->nullable();
            $table->decimal('calculated_duration', 6, 2)->nullable();
            $table->string('validation_status')->default('valid')->nullable();
            $table->text('validation_notes')->nullable();
            
            			$table->index('attendance_id');
			$table->index('start_time');
			$table->index('end_time');
			$table->index('session_type');
			$table->index('is_adjusted');
			$table->index(['attendance_id', 'start_time']);
			$table->index(['clock_in_event_id', 'clock_out_event_id']);
			$table->index(['start_time', 'end_time']);
			$table->unique(['attendance_id', 'start_time', 'end_time']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
