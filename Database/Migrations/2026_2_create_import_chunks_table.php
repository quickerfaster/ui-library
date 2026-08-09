<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('import_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('offset');
            $table->unsignedInteger('limit');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->unique(['import_id', 'chunk_index']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_chunks');
    }
};