<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // HR/business columns (consolidated from add_hr_columns_to_documents_table)
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id');
            $table->unsignedBigInteger('employee_id')->nullable()->after('company_id');
            $table->index('employee_id');
            $table->string('type')->nullable()->after('employee_id');
            $table->index('type');
            $table->string('document')->nullable()->after('type');
            $table->date('uploaded_at')->nullable()->after('document');
            $table->date('expiry_date')->nullable()->after('uploaded_at');
            $table->text('description')->nullable()->after('expiry_date');

            // Polymorphic documentable
            $table->morphs('documentable');
            $table->string('name');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->default(0);
            $table->string('document_type')->nullable();
            $table->string('disk')->default('public');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};