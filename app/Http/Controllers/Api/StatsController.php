<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkoutLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function getDashboardStats(): JsonResponse
    {
        $user = Auth::user();

        $currentStreak = $this->calculateCurrentStreak($user);

        $weeklyWorkouts = WorkoutLog::where('user_id', $user->id)
            ->whereBetween('completed_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->count();

        $totalWorkouts = WorkoutLog::where('user_id', $user->id)->count();
        $totalDuration = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        $caloriesBurned = $this->calculateTotalCalories($user);
        $goalsAchieved = $user->goals()->where('completed', true)->count();
        $personalRecords = $user->personalRecords()->count();

        return response()->json([
            'currentStreak' => $currentStreak,
            'stats' => [
                'weeklyWorkouts' => $weeklyWorkouts,
                'totalWorkouts' => $totalWorkouts,
                'totalDuration' => $totalDuration,
                'caloriesBurned' => $caloriesBurned,
                'goalsAchieved' => $goalsAchieved,
                'personalRecords' => $personalRecords,
            ],
        ]);
    }

    private function calculateCurrentStreak($user): int
    {
        // Iegūst unikālos treniņu datumus, jaunākie pirmie
        $dates = WorkoutLog::where('user_id', $user->id)
            ->orderBy('completed_at', 'desc')
            ->pluck('completed_at')
            ->map(fn($d) => $d->toDateString())
            ->unique()
            ->values();

        $streak = 0;
        $check = now()->toDateString();

        foreach ($dates as $date) {
            if ($date === $check) {
                $streak++;
                $check = now()->subDays($streak)->toDateString();
            } else {
                break;
            }
        }

        return $streak;
    }

    private function calculateTotalCalories($user): int
    {
        // 5 kalorijas minūtē
        $totalMinutes = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        return intval($totalMinutes * 5);
    }
}
