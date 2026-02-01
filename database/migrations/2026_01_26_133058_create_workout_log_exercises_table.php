<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workout_log_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_log_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->integer('sets_completed')->default(0);
            $table->json('reps_completed')->nullable(); // Store reps for each set
            $table->json('weights_used')->nullable();   // Store weights for each set
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['workout_log_id', 'exercise_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workout_log_exercises');
    }
};
