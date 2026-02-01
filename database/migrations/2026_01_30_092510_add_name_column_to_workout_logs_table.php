<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            // Pievienojiet name kolonnu
            $table->string('name')->nullable()->after('routine_id');
            
            // Ja nepieciešams, pievienojiet arī citas trūkstošās kolonnas
            $table->foreignId('workout_session_id')->nullable()->after('user_id')
                ->constrained('workout_sessions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->dropColumn(['name', 'workout_session_id']);
        });
    }
};
