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
        'reps_completed',
        'weights_used',
        'sets_planned',
        'reps_planned',
        'notes'
    ];
    
    protected $casts = [
        'reps_completed' => 'array',
        'weights_used' => 'array'
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
