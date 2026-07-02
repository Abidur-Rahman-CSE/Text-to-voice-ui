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
            $table->json('timestamps_data')->nullable()->after('speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_audio', function (Blueprint $table) {
            $table->dropColumn('timestamps_data');
        });
    }
};
