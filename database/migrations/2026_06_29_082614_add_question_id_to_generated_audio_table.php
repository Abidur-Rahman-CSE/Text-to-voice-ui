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
            $table->unsignedBigInteger('question_id')->nullable()->after('id');
            $table->text('deepseek_text')->nullable()->after('text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_audio', function (Blueprint $table) {
            $table->dropColumn(['question_id', 'deepseek_text']);
        });
    }
};
