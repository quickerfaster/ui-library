<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('causer_type');
            $table->integer('causer_id');
            $table->string('subject_type');
            $table->integer('subject_id');
            $table->string('log_name');
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('old_values');
            $table->json('new_values');
            $table->json('properties');
            $table->datetime('created_at');
            $table->datetime('updated_at');
            
            			$table->index('log_name');
			$table->index('action');
			$table->index('created_at');
			$table->index('causer_id');
			$table->index('subject_id');
			$table->index(['log_name', 'created_at']);
			$table->index(['action', 'created_at']);
			$table->index(['causer_type', 'causer_id']);
            
            
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
