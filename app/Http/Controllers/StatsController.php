<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use App\Models\WorkoutLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function getDashboardStats(Request $request)
    {
        $user = Auth::user();
        
        // Pašreizējā sērija (secīgas dienas ar treniņiem)
        $currentStreak = $this->calculateCurrentStreak($user);
        
        // Šonedēļas treniņu skaits
        $weeklyWorkouts = WorkoutLog::where('user_id', $user->id)
            ->whereBetween('completed_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->count();
        
        // Kopējais treniņu skaits
        $totalWorkouts = WorkoutLog::where('user_id', $user->id)->count();
        
        // Kopējais treniņu laiks
        $totalDuration = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        
        // Aprēķinātās sadedzinātās kalorijas
        $caloriesBurned = $this->calculateTotalCalories($user);
        
        // Sasniegtie mērķi
        $goalsAchieved = $user->goals()->where('completed', true)->count();
        
        // Personīgie rekordi
        $personalRecords = $user->personalRecords()->count();
        
        return response()->json([
            'currentStreak' => $currentStreak,
            'stats' => [
                'weeklyWorkouts' => $weeklyWorkouts,
                'totalWorkouts' => $totalWorkouts,
                'totalDuration' => $totalDuration,
                'caloriesBurned' => $caloriesBurned,
                'goalsAchieved' => $goalsAchieved,
                'personalRecords' => $personalRecords
            ]
        ]);
    }
    
    private function calculateCurrentStreak($user)
    {
        // Atrodam pēdējās 30 dienas ar treniņiem
        $workoutDays = WorkoutLog::where('user_id', $user->id)
            ->where('completed_at', '>=', now()->subDays(30))
            ->orderBy('completed_at', 'desc')
            ->pluck('completed_at')
            ->map(function($date) {
                return $date->format('Y-m-d');
            })
            ->unique()
            ->values()
            ->toArray();
        
        // Aprēķinām sēriju
        $streak = 0;
        $currentDate = now()->format('Y-m-d');
        
        foreach ($workoutDays as $i => $workoutDay) {
            if ($workoutDay == $currentDate || 
                ($i == 0 && $workoutDay == now()->subDay()->format('Y-m-d'))) {
                $streak++;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    private function calculateTotalCalories($user)
    {
        // Vienkāršs aprēķins: 5 kalorijas minūtē
        $totalMinutes = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        return intval($totalMinutes * 5);
    }
}
