<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSessionExercise extends Model
{
    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'sets_planned',
        'reps_planned',
        'sets_completed',
        'reps_completed',
        'weights_used',
        // Kardio laiki — sekundes katram setam
        'durations_completed',
    ];

    protected $casts = [
        'reps_completed'      => 'array',
        'weights_used'        => 'array',
        'durations_completed' => 'array',
    ];

    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
