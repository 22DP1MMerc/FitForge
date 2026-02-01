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
    
    
public function calculateWeeklyStreak($userId)
{
    $user = User::find($userId);
    
    // Iegūst visas pabeigtās sesijas
    $completedSessions = WorkoutSession::where('user_id', $userId)
        ->where('status', 'completed')
        ->whereNotNull('ended_at')
        ->orderBy('ended_at', 'desc')
        ->get();

    $currentStreak = 0;
    $lastWeekChecked = null;
    
    // Grupē sesijas pa nedēļām
    $weeklySessions = $completedSessions->groupBy(function ($session) {
        return $session->ended_at->startOfWeek()->format('Y-W');
    });

    // Pārbauda katru nedēļu secīgi
    foreach ($weeklySessions as $week => $sessions) {
        $weekStart = Carbon::createFromFormat('Y-W', $week)->startOfWeek();
        
        // Pārbauda vai šajā nedēļā bija vismaz 1 treniņš
        $hasWorkout = $sessions->count() > 0;
        
        // Ja pirmā pārbaudāmā nedēļa un tai ir treniņš, sākam streak
        if ($lastWeekChecked === null && $hasWorkout) {
            $currentStreak = 1;
            $lastWeekChecked = $weekStart;
            continue;
        }
        
        // Ja ir iepriekšējā nedēļa, pārbauda vai tā ir tieši pirms šīs nedēļas
        if ($lastWeekChecked && $hasWorkout) {
            $expectedPreviousWeek = $weekStart->copy()->subWeek();
            
            if ($lastWeekChecked->equalTo($expectedPreviousWeek)) {
                $currentStreak++;
                $lastWeekChecked = $weekStart;
            } else {
                // Streak pārtraukts
                break;
            }
        }
    }

    return $currentStreak;
}
    
    private function calculateTotalCalories($user)
    {
        // Vienkāršs aprēķins: 5 kalorijas minūtē
        $totalMinutes = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        return intval($totalMinutes * 5);
    }
}
