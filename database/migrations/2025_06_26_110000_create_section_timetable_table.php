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
        Schema::create('section_timetable', function (Blueprint $table) {
            $table->foreignId('timetable_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('section_id')->constrained()->onDelete('cascade');
            $table->primary(['timetable_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_timetable');
    }
};
