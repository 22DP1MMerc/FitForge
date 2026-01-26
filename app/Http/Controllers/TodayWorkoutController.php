<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodayWorkoutController extends Controller
{
    public function getTodayWorkout(Request $request)
    {
        $user = Auth::user();
        
        // Atrodam rutīnu, kas paredzēta šodienai (pēc dienas numura)
        $today = date('N'); // 1-7, kur 1=Pirmdiena
        
        $routine = Routine::with(['exercises' => function($query) use ($today) {
            $query->wherePivot('day_number', $today);
        }])
        ->where('user_id', $user->id)
        ->whereHas('exercises', function($query) use ($today) {
            $query->where('exercise_routine.day_number', $today);
        })
        ->first();
        
        if (!$routine) {
            return response()->json([
                'workout' => null,
                'message' => 'No workout scheduled for today'
            ]);
        }
        
        $workout = [
            'id' => $routine->id,
            'name' => $routine->name,
            'duration' => $this->calculateDuration($routine->exercises),
            'exercises' => $routine->exercises->map(function($exercise) {
                return [
                    'name' => $exercise->name,
                    'sets' => $exercise->pivot->sets . 'x' . $exercise->pivot->reps
                ];
            })->toArray()
        ];
        
        return response()->json([
            'workout' => $workout
        ]);
    }
    
    private function calculateDuration($exercises)
    {
        $totalSeconds = 0;
        foreach ($exercises as $exercise) {
            // Paredzam 2 minūtes katram setam
            $sets = $exercise->pivot->sets;
            $reps = $exercise->pivot->reps;
            $rest = $exercise->pivot->rest_seconds;
            
            // Laiks = (setu skaits * (30 sekundes vingrinājumam + atpūta))
            $totalSeconds += $sets * (30 + ($rest ?? 60));
        }
        
        $minutes = ceil($totalSeconds / 60);
        return $minutes . ' min';
    }
}
