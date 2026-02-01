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
        'calories_burned',
        'notes',
        'completed_at'
    ];
    
    protected $casts = [
        'completed_at' => 'datetime'
    ];
    
    // ✅ PIEVIENOJAM logExercises relāciju
    public function logExercises(): HasMany
    {
        return $this->hasMany(WorkoutLogExercise::class, 'workout_log_id');
    }
    
    // ✅ PIEVIENOJAM workoutSession relāciju (ja nepieciešams)
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
    
    // Palīgmetodes treniņa statistikas iegūšanai
    public function getTotalSetsAttribute()
    {
        return $this->logExercises->sum('sets_completed');
    }
    
    public function getTotalRepsAttribute()
    {
        $totalReps = 0;
        foreach ($this->logExercises as $logExercise) {
            $reps = $logExercise->reps_completed ?? [];
            foreach ($reps as $repCount) {
                $totalReps += is_numeric($repCount) ? $repCount : 0;
            }
        }
        return $totalReps;
    }
    
    public function getTotalWeightAttribute()
    {
        $totalWeight = 0;
        foreach ($this->logExercises as $logExercise) {
            $weights = $logExercise->weights_used ?? [];
            foreach ($weights as $weight) {
                if (is_array($weight) && isset($weight['weight'])) {
                    $totalWeight += floatval($weight['weight']);
                } elseif (is_numeric($weight)) {
                    $totalWeight += floatval($weight);
                }
            }
        }
        return $totalWeight;
    }
}
