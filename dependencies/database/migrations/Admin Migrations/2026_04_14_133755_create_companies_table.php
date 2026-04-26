<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_company_id')->nullable()->constrained('companies', 'id')->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained('locations', 'id')->onDelete('cascade');
            $table->string('subdomain')->nullable();
            $table->string('database_name')->nullable();
            $table->string('status')->default('pending')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_address_line_1')->nullable();
            $table->string('billing_address_line_2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state_province')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country_code')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('level')->default('division')->nullable();
            $table->boolean('is_placeholder')->default(true)->nullable();
            
            			$table->unique('subdomain');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
