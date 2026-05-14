<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('workout_session_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->integer('sets_planned')->default(0);
            $table->integer('reps_planned')->default(0);
            $table->text('notes_planned')->nullable();
            $table->integer('sets_completed')->default(0);
            $table->json('reps_completed')->nullable();
            $table->json('weights_used')->nullable();
            $table->json('durations_completed')->nullable();
            $table->text('notes_actual')->nullable();
            $table->timestamps();

            $table->index(['workout_session_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_session_exercises');
        Schema::dropIfExists('workout_sessions');
    }
};
