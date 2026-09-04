<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_action_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_id');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_action_histories');
    }
};
