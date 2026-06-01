<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->integer('approval_level')->default(1);
            $table->boolean('can_approve_all_types')->default(true);
            $table->foreignId('leave_type_ids')->nullable()->constrained('leave_types', 'id');
            $table->integer('max_approval_days')->nullable();
            $table->boolean('is_active')->default(true);
            
            
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_approvers');
    }
};
