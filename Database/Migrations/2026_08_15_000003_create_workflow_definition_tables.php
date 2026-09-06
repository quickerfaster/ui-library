<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('entity_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_definition_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_definition_id')
                ->constrained('workflow_definitions')
                ->cascadeOnDelete();
            $table->integer('sequence');
            $table->string('tier_type', 20);   // initiator, review, authorizer
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('resolution_mode', 10)->default('any'); // any, all
            $table->json('assignees')->nullable(); // { mode: "users|roles|mixed", ids: [...] }
            $table->timestamps();

            $table->unique(['workflow_definition_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_definition_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};