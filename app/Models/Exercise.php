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
        'image', 
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
    
    /**
     * Get the full URL for the exercise image
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            // Check if it's a full URL (starts with http:// or https://)
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            // If it's a local path, generate storage URL
            return asset('storage/' . $this->image);
        }
        
        // Return default placeholder based on muscle group
        return $this->getDefaultImage();
    }
    
    /**
     * Get default placeholder image based on muscle group
     */
    protected function getDefaultImage()
    {
        $defaultImages = [
            'Krūtis' => '/images/defaults/chest.jpg',
            'Mugura' => '/images/defaults/back.jpg',
            'Kājas' => '/images/defaults/legs.jpg',
            'Pleci' => '/images/defaults/shoulders.jpg',
            'Rokas' => '/images/defaults/arms.jpg',
            'Korsete' => '/images/defaults/core.jpg',
            'Kardio' => '/images/defaults/cardio.jpg',
            'Pilns ķermenis' => '/images/defaults/fullbody.jpg',
        ];
        
        $defaultImage = $defaultImages[$this->muscle_group] ?? '/images/defaults/exercise.jpg';
        
        return asset($defaultImage);
    }
}
