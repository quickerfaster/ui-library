<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types', 'id')->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('employees', 'id')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->text('denial_reason')->nullable();
            $table->boolean('attendance_synced')->default(false);
            $table->integer('attendance_records_count')->default(0);
            $table->datetime('last_sync_at')->nullable();
            $table->boolean('is_retroactive')->default(false);
            $table->boolean('reported_after_absence')->default(false);
            $table->integer('workdays_count')->nullable();
            $table->boolean('overlap_with_holiday')->default(false);
            
            			$table->index('employee_id');
			$table->index('status');
			$table->index('start_date');
			$table->index('end_date');
			$table->index('is_retroactive');
			$table->index(['employee_id', 'start_date']);
			$table->index(['status', 'created_at']);
			$table->index(['start_date', 'end_date']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_requests');
    }
};
