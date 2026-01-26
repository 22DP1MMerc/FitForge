<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->decimal('weight', 8, 2);
            $table->integer('reps');
            $table->integer('sets');
            $table->text('notes')->nullable();
            $table->date('achieved_at');
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('personal_records');
    }
};
