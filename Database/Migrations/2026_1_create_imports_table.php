<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('config_key');
            $table->string('file_path');
            $table->string('original_filename');
            // $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->string('error_file')->nullable();

            $table->string('status')->default('pending'); // pending, processing, completed, failed

            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('chunk_size')->nullable();
            $table->unsignedInteger('total_chunks')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();


            $table->index(['user_id', 'status', 'created_at']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
