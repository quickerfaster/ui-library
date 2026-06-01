<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clock_events', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id');
            $table->string('event_type');
            $table->datetime('timestamp');
            $table->string('method')->default('web');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->string('timezone')->default('UTC')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('sync_status')->default('pending')->nullable();
            $table->integer('sync_attempts')->default(0)->nullable();
            
            
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clock_events');
    }
};
