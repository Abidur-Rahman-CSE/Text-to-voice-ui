<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('generated_audio', function (Blueprint $table) {
            $table->string('model_type')->nullable()->after('deepseek_text');
            $table->string('voice')->nullable()->after('model_type');
            $table->string('speed')->nullable()->after('voice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_audio', function (Blueprint $table) {
            $table->dropColumn(['model_type', 'voice', 'speed']);
        });
    }
};
