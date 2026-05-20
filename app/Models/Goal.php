<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'exercise_id',
        'target_value',
        'current_value',
        'unit',
        'deadline',
        'completed',
    ];

    protected $casts = [
        'deadline'      => 'date',
        'completed'     => 'boolean',
        'target_value'  => 'float',
        'current_value' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercise()
    {
        return $this->belongsTo(\App\Models\Exercise::class);
    }

    public function isAutoTracked(): bool
    {
        return in_array($this->type, ['workout', 'strength', 'endurance']);
    }
}
