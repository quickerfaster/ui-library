<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('type');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body_template');
            $table->string('locale')->default('en');
            $table->timestamps();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->morphs('preferable');
            $table->string('type');
            $table->string('channel');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->string('type');
            $table->string('channel');
            $table->string('status')->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notifications');
    }
};