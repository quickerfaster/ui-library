<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_work_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->foreignId('work_pattern_id')->constrained('work_patterns', 'id')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            			$table->index('employee_id');
			$table->index('work_pattern_id');
			$table->index('start_date');
			$table->index('end_date');
			$table->index(['employee_id', 'start_date', 'end_date']);
			$table->index(['start_date', 'end_date']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_work_patterns');
    }
};
