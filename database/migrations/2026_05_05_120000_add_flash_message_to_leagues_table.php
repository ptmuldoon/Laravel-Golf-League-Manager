<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->text('flash_message')->nullable()->after('tee_time_interval');
            $table->boolean('flash_message_enabled')->default(false)->after('flash_message');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['flash_message', 'flash_message_enabled']);
        });
    }
};
