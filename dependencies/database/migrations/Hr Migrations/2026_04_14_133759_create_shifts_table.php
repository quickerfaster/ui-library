<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('duration_hours', 4, 2)->nullable();
            $table->boolean('is_overnight')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('shift_category')->default('regular')->nullable();
            $table->integer('created_from_template_id')->nullable();
            $table->date('last_used_date')->nullable();
            $table->integer('usage_count')->default(0);
            
            			$table->index('code');
			$table->index('is_active');
			$table->index('is_default');
			$table->index('shift_category');
			$table->index('is_overnight');
			$table->index(['is_active', 'is_default']);
			$table->index(['start_time', 'end_time']);
			$table->unique('code');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
    }
};
