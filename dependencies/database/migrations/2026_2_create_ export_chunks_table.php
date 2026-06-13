<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('export_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_id')->constrained('exports')->onDelete('cascade');
            $table->unsignedInteger('chunk_index');
            $table->string('file_path');
            $table->timestamps();

            // Optional: ensure one chunk index per export is unique
            $table->unique(['export_id', 'chunk_index']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('export_chunks');
    }
};