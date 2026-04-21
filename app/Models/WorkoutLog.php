<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    protected $fillable = [
        'user_id',
        'workout_session_id',
        'routine_id',
        'name',
        'duration_minutes',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function logExercises(): HasMany
    {
        return $this->hasMany(WorkoutLogExercise::class, 'workout_log_id');
    }

    public function workoutSession()
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'workout_log_exercises')
            ->withPivot('sets_completed', 'reps_completed', 'weights_used', 'notes')
            ->withTimestamps();
    }

    // Kopējais setu skaits
    public function getTotalSetsAttribute()
    {
        return $this->logExercises->sum('sets_completed');
    }

    // Kopējais atkārtojumu skaits (tikai strength)
    public function getTotalRepsAttribute()
    {
        $total = 0;
        foreach ($this->logExercises as $ex) {
            foreach ($ex->reps_completed ?? [] as $reps) {
                if (is_numeric($reps)) $total += $reps;
            }
        }
        return $total;
    }

    // Kopējais svars (tikai strength)
    public function getTotalWeightAttribute()
    {
        $total = 0;
        foreach ($this->logExercises as $ex) {
            foreach ($ex->weights_used ?? [] as $weight) {
                if (is_array($weight) && isset($weight['weight'])) {
                    $total += floatval($weight['weight']);
                } elseif (is_numeric($weight)) {
                    $total += floatval($weight);
                }
            }
        }
        return $total;
    }
}
