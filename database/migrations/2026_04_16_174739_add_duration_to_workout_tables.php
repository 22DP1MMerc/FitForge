<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_session_exercises', function (Blueprint $table) {
            $table->json('durations_completed')->nullable()->after('weights_used');
        });

        Schema::table('workout_log_exercises', function (Blueprint $table) {
            $table->json('durations_completed')->nullable()->after('weights_used');
        });
    }

    public function down(): void
    {
        Schema::table('workout_session_exercises', function (Blueprint $table) {
            $table->dropColumn('durations_completed');
        });

        Schema::table('workout_log_exercises', function (Blueprint $table) {
            $table->dropColumn('durations_completed');
        });
    }
};
