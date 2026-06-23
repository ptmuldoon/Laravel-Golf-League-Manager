<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // For multi-nine facilities: the nine played as the front (positions
            // 1-9) and, optionally, the nine played as the back (positions 10-18).
            // Null on both = legacy single-course front_9/back_9 behavior.
            $table->foreignId('front_nine_id')->nullable()->after('golf_course_id')
                ->constrained('course_nines')->nullOnDelete();
            $table->foreignId('back_nine_id')->nullable()->after('front_nine_id')
                ->constrained('course_nines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('front_nine_id');
            $table->dropConstrainedForeignId('back_nine_id');
        });
    }
};
