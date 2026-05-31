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
            $table->string('log_name')->nullable();
            $table->string('action');
            $table->text('description')->nullable();
            $table->datetime('created_at');
            $table->string('subject_type')->nullable();
            $table->integer('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->integer('causer_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('properties')->nullable();
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
