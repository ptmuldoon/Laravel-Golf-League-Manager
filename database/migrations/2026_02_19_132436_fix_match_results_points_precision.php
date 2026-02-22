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
        Schema::table('match_results', function (Blueprint $table) {
            $table->decimal('team_points_home', 4, 2)->default(0)->change();
            $table->decimal('team_points_away', 4, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            $table->decimal('team_points_home', 3, 1)->default(0)->change();
            $table->decimal('team_points_away', 3, 1)->default(0)->change();
        });
    }
};
