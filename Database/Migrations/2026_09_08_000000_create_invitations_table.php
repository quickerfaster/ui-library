<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token', 64)->nullable()->unique();
            $table->string('status')->default('pending');
            $table->string('role')->nullable();
            $table->string('invitable_type')->nullable();
            $table->unsignedBigInteger('invitable_id')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['invitable_type', 'invitable_id']);
            $table->index('status');
            $table->index('token');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};