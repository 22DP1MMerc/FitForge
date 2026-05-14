<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('routine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->integer('calories_burned')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
