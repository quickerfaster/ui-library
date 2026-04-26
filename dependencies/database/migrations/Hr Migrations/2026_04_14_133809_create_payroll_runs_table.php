<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pay_schedule_id')->constrained('pay_schedules', 'id')->onDelete('restrict');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('Draft');
            $table->string('processed_by')->nullable();
            $table->datetime('processed_at')->nullable();
            $table->text('notes')->nullable();
            
            
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_runs');
    }
};
