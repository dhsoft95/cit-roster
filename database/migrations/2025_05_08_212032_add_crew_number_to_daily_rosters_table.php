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
        Schema::table('daily_rosters', function (Blueprint $table) {
            $table->unsignedTinyInteger('crew_number')->default(1)->after('crew_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_rosters', function (Blueprint $table) {
            $table->dropColumn('crew_number');
        });
    }
};
