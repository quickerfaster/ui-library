<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->foreignId('company_id')->constrained('companies', 'id')->onDelete('cascade');
            $table->foreignId('parent_department_id')->nullable()->constrained('departments', 'id')->onDelete('set null');
            $table->string('cost_center')->nullable();
            $table->boolean('is_active')->default(true)->nullable();
            
            			$table->index('code');
			$table->index('company_id');
			$table->index('parent_department_id');
			$table->index('is_active');
			$table->index('deleted_at');
			$table->index(['company_id', 'is_active']);
			$table->index(['parent_department_id', 'is_active']);
			$table->unique('code');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};
