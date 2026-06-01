<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {


    public function up()
    {
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('config_key');           // e.g., 'hr.employee'
            $table->string('name');
            $table->string('type');                 // 'tabular' or 'dashboard'
            $table->json('configuration');          // stores fields, filters, widgets, layout, etc.
            $table->boolean('is_global')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_reports');
    }


};
