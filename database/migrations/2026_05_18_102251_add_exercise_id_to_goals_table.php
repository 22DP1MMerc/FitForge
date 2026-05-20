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
        Schema::table('goals', function (Blueprint $table) {
            $table->unsignedBigInteger('exercise_id')->nullable()->after('type');
            $table->foreign('exercise_id')->references('id')->on('exercises')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropForeign(['exercise_id']);
            $table->dropColumn('exercise_id');
        });
    }
};
