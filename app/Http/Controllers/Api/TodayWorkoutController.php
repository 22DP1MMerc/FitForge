<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodayWorkoutController extends Controller
{
    public function getTodayWorkout(Request $request): JsonResponse
    {
        $user = Auth::user();

        // 1 = Pirmdiena, 7 = Svētdiena
        $today = date('N');

        $routine = Routine::with(['exercises' => function ($query) use ($today) {
            $query->wherePivot('day_number', $today);
        }])
            ->where('user_id', $user->id)
            ->whereHas('exercises', function ($query) use ($today) {
                $query->where('exercise_routine.day_number', $today);
            })
            ->first();

        if (!$routine) {
            return response()->json([
                'workout' => null,
                'message' => 'No workout scheduled for today',
            ]);
        }

        $workout = [
            'id' => $routine->id,
            'name' => $routine->name,
            'duration' => $this->calculateDuration($routine->exercises),
            'exercises' => $routine->exercises->map(fn($exercise) => [
                'name' => $exercise->name,
                'sets' => $exercise->pivot->sets . 'x' . $exercise->pivot->reps,
            ])->toArray(),
        ];

        return response()->json(['workout' => $workout]);
    }

    private function calculateDuration($exercises): string
    {
        $totalSeconds = 0;

        foreach ($exercises as $exercise) {
            $sets = $exercise->pivot->sets;
            $rest = $exercise->pivot->rest_seconds ?? 60;

            // 30 sekundes vingrinājumam + atpūta starp setiem
            $totalSeconds += $sets * (30 + $rest);
        }

        return ceil($totalSeconds / 60) . ' min';
    }
}
