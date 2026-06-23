<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A first-class "nine" belonging to a course/facility. A standard
        // 18-hole course needs no nines (legacy course_info is used directly);
        // a multi-nine facility (e.g. three nines that combine to 18) defines
        // one row per nine, and its holes live in course_info tagged with
        // course_nine_id.
        Schema::create('course_nines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('golf_course_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g. "Ocean", "Canyon", "A"
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['golf_course_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_nines');
    }
};
