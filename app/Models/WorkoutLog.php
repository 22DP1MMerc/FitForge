<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutLog extends Model
{
    protected $fillable = [
        'user_id',
        'routine_id',
        'duration_minutes',
        'calories_burned',
        'notes',
        'completed_at'
    ];
    
    protected $casts = [
        'completed_at' => 'datetime'
    ];
    
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
            ->withPivot(['sets_completed', 'reps_completed', 'weight_used'])
            ->withTimestamps();
    }
}
