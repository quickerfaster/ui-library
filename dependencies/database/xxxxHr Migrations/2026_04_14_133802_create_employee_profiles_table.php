<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('photo')->nullable();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->string('middle_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('personal_email');
            $table->string('personal_phone')->nullable();
            $table->string('work_phone')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('national_id_number')->nullable();
            $table->text('bio')->nullable();
            
            			$table->unique('employee_id');
			$table->unique('personal_email');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_profiles');
    }
};
