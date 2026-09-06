<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('systems')) {
            Schema::create('systems', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });

            // Seed the singleton row
            \QuickerFaster\UILibrary\Models\System::create(['id' => 1]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
