<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Fixed dollar amount each player on a segment's winning team receives.
            $table->decimal('segment_winner_payout', 8, 2)->nullable()->after('par3_payout');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('segment_winner_payout');
        });
    }
};
