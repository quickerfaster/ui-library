<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique(); // Unique index handles lookups automatically
            $table->string('status')->default("invited");
            $table->string('password'); // Changed from text() to string() for standard hash storage
            $table->timestamp('email_verified_at')->nullable(); // Using standard timestamp
            $table->rememberToken(); // Replaced with standard Laravel helper
            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes
			$table->index('status');
			$table->index('deleted_at');
			$table->index(['status', 'email']); // Composite index for filtered lookups
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
