<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'id')->onDelete('cascade');
            $table->string('document');
            $table->string('name');
            $table->string('type');
            $table->date('uploaded_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('description')->nullable();
            
            			$table->unique('employee_id');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents');
    }
};
