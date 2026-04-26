<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_payroll_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->foreignId('pay_schedule_id')->constrained('pay_schedules', 'id')->onDelete('restrict');
            $table->text('bank_account')->nullable();
            $table->text('bank_routing')->nullable();
            $table->string('tax_filing_status')->nullable();
            $table->integer('allowances')->default(0)->nullable();
            $table->boolean('is_exempt_from_federal_tax')->default(false)->nullable();
            $table->string('currency')->default('USD');
            
            
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_payroll_profiles');
    }
};
