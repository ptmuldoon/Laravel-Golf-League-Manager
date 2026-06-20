<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_finances', function (Blueprint $table) {
            // Tags payout entries to the segment they were paid for (idempotency
            // + per-season reporting). Null for ordinary fee/winnings entries.
            $table->foreignId('league_segment_id')->nullable()->after('league_id')
                ->constrained('league_segments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('league_finances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('league_segment_id');
        });
    }
};
