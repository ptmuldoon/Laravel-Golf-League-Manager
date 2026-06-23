<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_info', function (Blueprint $table) {
            // When set, this hole belongs to a specific nine (hole_number 1-9
            // within that nine, and rating/slope hold the nine's 9-hole values).
            // Null = legacy 18-hole course row. Additive and backward compatible.
            $table->foreignId('course_nine_id')->nullable()->after('golf_course_id')
                ->constrained('course_nines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_info', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_nine_id');
        });
    }
};
