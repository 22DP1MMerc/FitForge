<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
