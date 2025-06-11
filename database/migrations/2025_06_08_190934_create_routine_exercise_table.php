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
Schema::create('exercise_routine', function (Blueprint $table) {
    $table->id();
    $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
    $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
    $table->integer('day_number'); // 1-7 for Monday-Sunday
    $table->integer('sets');
    $table->integer('reps');
    $table->integer('rest_seconds')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routine_exercise');
    }
};

