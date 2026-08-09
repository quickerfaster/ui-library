<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_data_items', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('key');
            $table->json('value');
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_data_items');
    }
};