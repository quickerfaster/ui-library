<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Create approval_requests (without current_tier_id FK)
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->unsignedBigInteger('current_tier_id')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('submitted_by');
            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
        });

        // 2. Create approval_tiers
        Schema::create('approval_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')
                  ->constrained('approval_requests')
                  ->cascadeOnDelete();
            $table->enum('tier_type', ['initiation', 'reviewing', 'authorization']);
            $table->unsignedInteger('sequence');
            $table->string('name');
            $table->json('roles');
            $table->enum('approval_mode', ['any', 'all'])->default('any');
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'sequence']);
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // 3. Create approval_tier_approvals (explicit foreign key to approval_tiers)
        Schema::create('approval_tier_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tier_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['tier_id', 'user_id']);
            $table->foreign('tier_id')->references('id')->on('approval_tiers')->cascadeOnDelete();
        });

        // 4. Create approval_logs (explicit foreign keys)
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_request_id');
            $table->foreignId('user_id')->constrained();
            $table->string('action', 50);
            $table->unsignedBigInteger('tier_id')->nullable();
            $table->text('comments')->nullable();
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->timestamps();

            $table->index('approval_request_id');
            $table->foreign('approval_request_id')->references('id')->on('approval_requests')->cascadeOnDelete();
            $table->foreign('tier_id')->references('id')->on('approval_tiers')->nullOnDelete();
        });

        // 5. Add current_tier_id foreign key to approval_requests
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->foreign('current_tier_id')->references('id')->on('approval_tiers')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropForeign(['current_tier_id']);
        });
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('approval_tier_approvals');
        Schema::dropIfExists('approval_tiers');
        Schema::dropIfExists('approval_requests');
    }
};
