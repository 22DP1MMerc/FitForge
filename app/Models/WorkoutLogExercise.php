<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLogExercise extends Model
{
    protected $fillable = [
        'workout_log_id',
        'exercise_id',
        'sets_completed',
        'sets_planned',
        'reps_planned',
        'reps_completed',
        'weights_used',
        // Kardio laiki — katram setam sekundēs
        'durations_completed',
        'notes',
    ];

    protected $casts = [
        'reps_completed'      => 'array',
        'weights_used'        => 'array',
        'durations_completed' => 'array',
    ];

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
