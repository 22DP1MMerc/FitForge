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

        // ja pēdējais treniņš nav šodien vai vakar — sērija beigusies
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $firstDate = $dates->first();

        if ($firstDate !== $today && $firstDate !== $yesterday) {
            return 0;
        }

        $streak = 0;
        $expected = $firstDate;

        foreach ($dates as $date) {
            if ($date === $expected) {
                $streak++;
                // nākamā gaidāmā diena
                $expected = \Carbon\Carbon::parse($expected)->subDay()->toDateString();
            } else {
                break;
            }
        }

        return $streak;
    }

}
