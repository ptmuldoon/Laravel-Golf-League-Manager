<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            // Effective course rating/slope for this round. Set for multi-nine
            // rounds (combined values across the played nines); null for legacy
            // single-course rounds, which derive rating/slope from course_info.
            $table->decimal('rating', 4, 1)->nullable()->after('holes_played');
            $table->decimal('slope', 5, 1)->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn(['rating', 'slope']);
        });
    }
};
