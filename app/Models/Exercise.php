<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'muscle_group',
        'equipment'
    ];

    // Filter scopes
    public function scopeFilterByMuscleGroup($query, $muscleGroup)
    {
        return $query->where('muscle_group', $muscleGroup);
    }

    public function scopeFilterByEquipment($query, $equipment)
    {
        return $query->where('equipment', $equipment);
    }

    public function routines()
{
    return $this->belongsToMany(Routine::class)
        ->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes']);
}
}