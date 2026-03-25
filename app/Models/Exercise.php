<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Exercise extends Model
{
    protected $fillable = [
        'name',
        'description',
        'muscle_group',
        'equipment',
        'instructions',
        'difficulty',
    ];
    
    /**
     * Relationship with personal records
     */
    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class);
    }
    
    /**
     * Scope to filter by muscle group
     */
    public function scopeFilterByMuscleGroup(Builder $query, string $muscleGroup): Builder
    {
        return $query->where('muscle_group', $muscleGroup);
    }
    
    /**
     * Scope to filter by equipment
     */
    public function scopeFilterByEquipment(Builder $query, string $equipment): Builder
    {
        return $query->where('equipment', $equipment);
    }
    
    /**
     * Get the user's personal record for this exercise
     */
    public function getUserPersonalRecord($userId = null)
    {
        if (!$userId && auth()->check()) {
            $userId = auth()->id();
        }
        
        if (!$userId) {
            return null;
        }
        
        return $this->personalRecords()
            ->where('user_id', $userId)
            ->orderBy('weight', 'desc')
            ->orderBy('reps', 'desc')
            ->first();
    }
}
