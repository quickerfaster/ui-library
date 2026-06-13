<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('config_key');
            $table->json('filters')->nullable();      // active filters
            $table->json('columns')->nullable();       // selected columns
            $table->string('format');                  // csv, xls, pdf
            $table->json('options')->nullable();       // pdf orientation, etc.
            $table->string('status')->default('pending'); // pending, processing, completed, failed

            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('chunk_size')->nullable();
            $table->unsignedInteger('total_chunks')->nullable();

            $table->string('file_path')->nullable();


            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('download_token', 64)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();

            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);

        });
    }

    public function down()
    {
        Schema::dropIfExists('exports');
    }
};
