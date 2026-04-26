<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->boolean('deducts_from_balance')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->integer('max_days_per_request')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            
            			$table->unique('code');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
};
