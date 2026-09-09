<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invitation_id');
            $table->string('action'); // sent, resend, accepted, expired, revoked, reminded
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('invitation_id')
                ->references('id')
                ->on('invitations')
                ->cascadeOnDelete();

            $table->foreign('performed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('action');
            $table->index('invitation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_logs');
    }
};