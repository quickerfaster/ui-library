<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();
            $table->string('payslip_number');
            $table->foreignId('payroll_run_id')->constrained('payroll_runs', 'id')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('restrict');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('gross_pay', 12, 2);
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->datetime('paid_at')->nullable();
            
            			$table->unique('payslip_number');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_payslips');
    }
};
