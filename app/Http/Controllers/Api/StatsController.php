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

        // šīs nedēļas treniņi
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
        // unikālie datumi, jaunākie pirmie
        $dates = WorkoutLog::where('user_id', $user->id)
            ->orderBy('completed_at', 'desc')
            ->pluck('completed_at')
            ->map(fn($d) => $d->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        // sākam no šodienas vai vakardienas — ja šodien nav treniņa, sērija vēl var turpināties
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $firstDate = $dates->first();

        if ($firstDate !== $today && $firstDate !== $yesterday) {
            return 0; // pārāk sen, sērija pārtrūkusi
        }

        $streak = 0;
        $expected = $firstDate;

        foreach ($dates as $date) {
            if ($date === $expected) {
                $streak++;
                // nākamā gaidāmā diena
                $expected = now()->subDays(
                    now()->diffInDays(\Carbon\Carbon::parse($expected)) + 1
                )->toDateString();
            } else {
                break; // roба — sērija beidzas
            }
        }

        return $streak;
    }

    private function calculateTotalCalories($user): int
    {
        // aptuveni 5 kcal/min, pietiekami precīzi
        $totalMinutes = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        return intval($totalMinutes * 5);
    }
}
