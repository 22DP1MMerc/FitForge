<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSession extends Model
{
    protected $fillable = [
        'user_id',
        'routine_id',
        'name',
        'status',
        'started_at',
        'ended_at',
        'duration_minutes',
        'calories_burned',
        'notes'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime'
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }
    
    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutSessionExercise::class)->orderBy('order');
    }
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
