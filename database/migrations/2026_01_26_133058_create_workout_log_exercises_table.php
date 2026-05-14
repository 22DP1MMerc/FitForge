<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_log_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->integer('sets_completed')->default(0);
            $table->json('reps_completed')->nullable();
            $table->json('weights_used')->nullable();
            $table->json('durations_completed')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['workout_log_id', 'exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_log_exercises');
    }
};
