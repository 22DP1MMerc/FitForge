<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Routine extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id', 'is_public'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

public function exercises()
{
    return $this->belongsToMany(Exercise::class, 'exercise_routine')
        ->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes'])
        ->orderByPivot('day_number')
        ->withTimestamps();
}

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}