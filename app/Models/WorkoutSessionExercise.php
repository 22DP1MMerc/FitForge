<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSessionExercise extends Model
{
    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'order',
        'sets_planned',
        'reps_planned',
        'rest_seconds_planned',
        'notes_planned',
        'sets_completed',
        'reps_completed',
        'weights_used',
        'notes_actual'
    ];
    
    protected $casts = [
        'reps_completed' => 'array',
        'weights_used' => 'array'
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
