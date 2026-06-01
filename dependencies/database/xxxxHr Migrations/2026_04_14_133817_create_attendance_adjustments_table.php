<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances', 'id')->onDelete('cascade');
            $table->decimal('original_net_hours', 6, 2)->nullable();
            $table->string('original_status')->nullable();
            $table->decimal('adjusted_net_hours', 6, 2);
            $table->string('adjusted_status');
            $table->text('reason');
            $table->string('adjusted_by')->nullable();
            $table->datetime('adjusted_at')->nullable();
            
            			$table->index('attendance_id');
			$table->index('adjusted_at');
			$table->index('adjusted_status');
			$table->index(['attendance_id', 'adjusted_at']);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_adjustments');
    }
};
