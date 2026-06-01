<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types', 'id')->onDelete('cascade');
            $table->decimal('balance', 6, 2)->default(0);
            $table->decimal('accrual_rate', 6, 2)->default(1)->nullable();
            $table->string('accrual_frequency')->default('Monthly');
            $table->integer('year');
            
            
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_balances');
    }
};
