<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('payroll_run_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->onDelete('cascade');
            $table->integer('total_employees')->default(0);
            $table->integer('processed_employees')->default(0);
            $table->string('status')->default('pending'); // processing, completed, failed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_run_progress');
    }
};