<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    protected $fillable = [
        'user_id',
        'exercise_id',
        'weight',
        'reps',
        'sets',
        'notes',
        'achieved_at'
    ];
    
    protected $casts = [
        'achieved_at' => 'date'
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
    
    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class, 'workout_log_id');
    }
    
    // Add this scope to find best record for exercise
    public function scopeForExercise($query, $exerciseId, $userId)
    {
        return $query->where('exercise_id', $exerciseId)
                     ->where('user_id', $userId)
                     ->orderBy('weight', 'desc')
                     ->orderBy('reps', 'desc')
                     ->orderBy('achieved_at', 'desc');
    }
    
    // Helper method to check if this is a new record
    public static function isNewRecord($userId, $exerciseId, $weight, $reps)
    {
        $currentRecord = self::forExercise($exerciseId, $userId)->first();
        
        if (!$currentRecord) {
            return true; // No existing record, so this is a new record
        }
        
        // Check if new weight is higher OR same weight with more reps
        return $weight > $currentRecord->weight || 
               ($weight == $currentRecord->weight && $reps > $currentRecord->reps);
    }
}
