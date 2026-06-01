<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->morphs('settingable');          // settingable_type, settingable_id
            $table->string('key');
            $table->json('value')->nullable();
            $table->string('group')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['settingable_type', 'settingable_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};