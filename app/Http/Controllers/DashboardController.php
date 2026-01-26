<?php

namespace App\Http\Controllers;

use App\Models\WorkoutLog;
use App\Models\PersonalRecord;
use App\Models\Goal;
use App\Models\Routine;
use App\Models\WorkoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\WorkoutSessionExercise;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. Pārbaudiet vai ir aktīvs treniņš
        $activeWorkout = WorkoutSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['routine', 'exercises.exercise'])
            ->first();
        
        // 2. Ja IR aktīvs treniņš, rādiet to
        if ($activeWorkout) {
            return $this->showActiveWorkout($activeWorkout);
        }
        
        // 3. Ja NAV aktīva treniņa, rādiet normālo dashboard
        return $this->showNormalDashboard($user);
    }

    private function showActiveWorkout($activeWorkout)
    {
        $timeElapsed = $activeWorkout->started_at->diffInSeconds(now());
        
        return inertia('Dashboard', [
            'auth' => [
                'user' => $activeWorkout->user
            ],
            'hasActiveWorkout' => true,
            'activeWorkout' => [
                'id' => $activeWorkout->id,
                'name' => $activeWorkout->name,
                'routine_name' => $activeWorkout->routine?->name,
                'started_at' => $activeWorkout->started_at,
                'time_elapsed' => $timeElapsed,
                'exercises' => $activeWorkout->exercises->map(function($sessionExercise) {
                    return [
                        'id' => $sessionExercise->exercise_id,
                        'name' => $sessionExercise->exercise->name,
                        'sets_planned' => $sessionExercise->sets_planned,
                        'reps_planned' => $sessionExercise->reps_planned,
                        'sets_completed' => $sessionExercise->sets_completed,
                        'reps_completed' => $sessionExercise->reps_completed ?? [],
                        'weights_used' => $sessionExercise->weights_used ?? []
                    ];
                })
            ],
            // Pievienojiet arī normālos dashboard datus
            'stats' => $this->getStats($activeWorkout->user),
            'todayWorkout' => $this->getTodayWorkout($activeWorkout->user),
            'recentActivities' => [],
            'weeklySchedule' => [],
            'weeklyWeightStats' => $weeklyWeightStats
        ]);
    }

    private function showNormalDashboard($user)
    {
        // 1. Pašreizējā sērija
        $currentStreak = $this->calculateCurrentStreak($user);
        
        // 2. Šonedēļas treniņi
        $weeklyWorkouts = WorkoutLog::where('user_id', $user->id)
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
        
        // 3. Kopējais treniņu skaits
        $totalWorkouts = WorkoutLog::where('user_id', $user->id)->count();
        
        // 4. Kopējais laiks
        $totalDuration = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        
        // 5. Kalorijas
        $caloriesBurned = WorkoutLog::where('user_id', $user->id)->sum('calories_burned') ?? 0;
        
        // 6. Personīgie rekordi
        $personalRecords = PersonalRecord::where('user_id', $user->id)->count();
        
        // 7. Sasniegtie mērķi
        $goalsAchieved = Goal::where('user_id', $user->id)
            ->where('completed', true)
            ->count();
        
        // DEBUG: Pārbaudiet skaitus
        logger('Weekly workouts: ' . $weeklyWorkouts);
        logger('Total workouts: ' . $totalWorkouts);
        logger('Personal records: ' . $personalRecords);
        logger('Goals achieved: ' . $goalsAchieved);
        
        // 8. Nedēļas progress (vienkārša versija)
        $weeklyProgress = [
            'monday' => 0,
            'tuesday' => 0,
            'wednesday' => 0,
            'thursday' => 0,
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0
        ];
        
        // Aprēķiniet progress pēdējās 7 dienās
        for ($i = 0; $i < 7; $i++) {
            $day = Carbon::now()->startOfWeek()->addDays($i);
            $dayKey = strtolower($day->format('l'));
            
            $hasWorkout = WorkoutLog::where('user_id', $user->id)
                ->whereDate('completed_at', $day->format('Y-m-d'))
                ->exists();
            
            $weeklyProgress[$dayKey] = $hasWorkout ? 100 : 0;
        }
        
        // 9. Šodienas treniņš
        $todayWorkout = $this->getTodayWorkout($user);
        
        // 10. Nesenie notikumi
        $recentActivities = $this->getRecentActivities($user);
        
        // 11. Nedēļas grafiks
        $weeklySchedule = $this->getWeeklySchedule($user);

        $weeklyWeightStats = $this->getWeeklyWeightStats($user);

        return inertia('Dashboard', [
            'auth' => [
                'user' => $user
            ],
            'hasActiveWorkout' => false,
            'stats' => [
                'currentStreak' => $currentStreak,
                'weeklyWorkouts' => $weeklyWorkouts,
                'totalWorkouts' => $totalWorkouts,
                'totalDuration' => $totalDuration,
                'caloriesBurned' => $caloriesBurned,
                'goalsAchieved' => $goalsAchieved,
                'personalRecords' => $personalRecords,
                'weeklyProgress' => $weeklyProgress
            ],
            'todayWorkout' => $todayWorkout,
            'recentActivities' => $recentActivities,
            'weeklySchedule' => $weeklySchedule,
            'weeklyWeightStats' => $weeklyWeightStats
        ]);
    }
    
    private function getStats($user)
    {
        // Šī metode tiek izsaukta no showActiveWorkout, tāpēc vajag atkārtot aprēķinus
        $currentStreak = $this->calculateCurrentStreak($user);
        $weeklyWorkouts = WorkoutLog::where('user_id', $user->id)
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
        
        $totalWorkouts = WorkoutLog::where('user_id', $user->id)->count();
        $totalDuration = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        $caloriesBurned = WorkoutLog::where('user_id', $user->id)->sum('calories_burned') ?? 0;
        $personalRecords = PersonalRecord::where('user_id', $user->id)->count();
        $goalsAchieved = Goal::where('user_id', $user->id)
            ->where('completed', true)
            ->count();
        
        // Nedēļas progress
        $weeklyProgress = [
            'monday' => 0,
            'tuesday' => 0,
            'wednesday' => 0,
            'thursday' => 0,
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0
        ];
        
        for ($i = 0; $i < 7; $i++) {
            $day = Carbon::now()->startOfWeek()->addDays($i);
            $dayKey = strtolower($day->format('l'));
            
            $hasWorkout = WorkoutLog::where('user_id', $user->id)
                ->whereDate('completed_at', $day->format('Y-m-d'))
                ->exists();
            
            $weeklyProgress[$dayKey] = $hasWorkout ? 100 : 0;
        }
        
        return [
            'currentStreak' => $currentStreak,
            'weeklyWorkouts' => $weeklyWorkouts,
            'totalWorkouts' => $totalWorkouts,
            'totalDuration' => $totalDuration,
            'caloriesBurned' => $caloriesBurned,
            'goalsAchieved' => $goalsAchieved,
            'personalRecords' => $personalRecords,
            'weeklyProgress' => $weeklyProgress
        ];
    }
    
    private function calculateCurrentStreak($user)
    {
        // Vienkārša versija - skaita dienas pēc kārtas ar treniņiem
        $workoutDays = WorkoutLog::where('user_id', $user->id)
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('completed_at', 'desc')
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->completed_at)->format('Y-m-d');
            })
            ->keys()
            ->toArray();
        
        $streak = 0;
        $currentDate = Carbon::now();
        
        foreach ($workoutDays as $workoutDay) {
            $workoutDate = Carbon::parse($workoutDay);
            
            if ($workoutDate->isSameDay($currentDate) || 
                $workoutDate->isSameDay($currentDate->copy()->subDay())) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    private function getTodayWorkout($user)
    {
        $today = Carbon::now();
        $dayNumber = $today->dayOfWeekIso; // 1-7
        
        // Atrodam rutīnu šodienai
        $routine = Routine::with(['exercises' => function($query) use ($dayNumber) {
                $query->where('exercise_routine.day_number', $dayNumber);
            }])
            ->where('user_id', $user->id)
            ->whereHas('exercises', function($query) use ($dayNumber) {
                $query->where('exercise_routine.day_number', $dayNumber);
            })
            ->first();
        
        if (!$routine) {
            return null;
        }
        
        // Aprēķiniet ilgumu
        $duration = 0;
        foreach ($routine->exercises as $exercise) {
            $sets = $exercise->pivot->sets ?? 3;
            $restSeconds = $exercise->pivot->rest_seconds ?? 60;
            $duration += ($sets * 45) + ($sets * $restSeconds);
        }
        $durationMinutes = ceil($duration / 60);
        
        return [
            'id' => $routine->id,
            'name' => $routine->name,
            'duration' => $durationMinutes . ' min',
            'exercises' => $routine->exercises->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'sets' => $exercise->pivot->sets ?? 3,
                    'reps' => $exercise->pivot->reps ?? 10,
                    'rest_seconds' => $exercise->pivot->rest_seconds ?? 60
                ];
            })->toArray()
        ];
    }
    
    private function getRecentActivities($user)
    {
        $activities = [];
        
        // Pēdējie 3 treniņi
        $recentWorkouts = WorkoutLog::with('routine')
            ->where('user_id', $user->id)
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentWorkouts as $workout) {
            $timeAgo = Carbon::parse($workout->completed_at)->diffForHumans(['parts' => 1]);
            $activities[] = [
                'id' => $workout->id,
                'type' => 'workout',
                'title' => 'Pabeigts: ' . ($workout->routine->name ?? 'Treniņš'),
                'description' => $workout->duration_minutes . ' min, ' . 
                               ($workout->calories_burned ?? '0') . ' kalorijas',
                'time' => $timeAgo,
                'icon' => '✅'
            ];
        }
        
        // Pēdējie 2 personīgie rekordi
        $recentPRs = PersonalRecord::with('exercise')
            ->where('user_id', $user->id)
            ->orderBy('achieved_at', 'desc')
            ->limit(2)
            ->get();
        
        foreach ($recentPRs as $pr) {
            $timeAgo = Carbon::parse($pr->achieved_at)->diffForHumans(['parts' => 1]);
            $activities[] = [
                'id' => $pr->id,
                'type' => 'record',
                'title' => 'Jauns PR: ' . ($pr->exercise->name ?? 'Vingrinājums'),
                'description' => $pr->weight . 'kg, ' . $pr->reps . ' atkārtojumi',
                'time' => $timeAgo,
                'icon' => '🏆'
            ];
        }
        
        // Pabeigtie mērķi
        $completedGoals = Goal::where('user_id', $user->id)
            ->where('completed', true)
            ->orderBy('updated_at', 'desc')
            ->limit(2)
            ->get();
        
        foreach ($completedGoals as $goal) {
            $timeAgo = Carbon::parse($goal->updated_at)->diffForHumans(['parts' => 1]);
            $activities[] = [
                'id' => $goal->id,
                'type' => 'goal',
                'title' => 'Sasniegts: ' . $goal->title,
                'description' => $goal->current_value . ' ' . $goal->unit,
                'time' => $timeAgo,
                'icon' => '🎯'
            ];
        }
        
        // Sakārtot pēc laika
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 3);
    }

    private function getWeeklyWeightStats($user)
{
    $weekStart = Carbon::now()->startOfWeek();
    $weekEnd = Carbon::now()->endOfWeek();
    
    // Iegūst treniņus šajā nedēļā
    $workouts = WorkoutLog::where('user_id', $user->id)
        ->whereBetween('completed_at', [$weekStart, $weekEnd])
        ->with(['routine', 'exercises'])
        ->get();
    
    $weightStats = [
        'monday' => ['totalWeight' => 0, 'exercises' => 0],
        'tuesday' => ['totalWeight' => 0, 'exercises' => 0],
        'wednesday' => ['totalWeight' => 0, 'exercises' => 0],
        'thursday' => ['totalWeight' => 0, 'exercises' => 0],
        'friday' => ['totalWeight' => 0, 'exercises' => 0],
        'saturday' => ['totalWeight' => 0, 'exercises' => 0],
        'sunday' => ['totalWeight' => 0, 'exercises' => 0]
    ];
    
    foreach ($workouts as $workout) {
        $dayOfWeek = strtolower(Carbon::parse($workout->completed_at)->format('l'));
        
        // Aprēķina kopējo svaru no vingrinājumiem
        $totalWeight = 0;
        $exerciseCount = 0;
        
        // Ja ir vingrinājumi (ja ir saistīta tabula)
        if ($workout->exercises) {
            foreach ($workout->exercises as $exercise) {
                // Aprēķina svaru no setiem
                if (isset($exercise->pivot->weights_used)) {
                    $weights = $exercise->pivot->weights_used;
                    if (is_array($weights)) {
                        foreach ($weights as $weight) {
                            $totalWeight += floatval($weight);
                        }
                    }
                    $exerciseCount++;
                }
            }
        }
        
        $weightStats[$dayOfWeek]['totalWeight'] += $totalWeight;
        $weightStats[$dayOfWeek]['exercises'] += $exerciseCount;
    }
    
    return $weightStats;
}

    
    private function getWeeklySchedule($user)
    {
        $schedule = [];
        $days = ['Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena', 'Svētdiena'];
        
        for ($dayNumber = 1; $dayNumber <= 7; $dayNumber++) {
            // Atrodam rutīnu šai dienai
            $routine = Routine::with(['exercises' => function($query) use ($dayNumber) {
                    $query->where('exercise_routine.day_number', $dayNumber);
                }])
                ->where('user_id', $user->id)
                ->whereHas('exercises', function($query) use ($dayNumber) {
                    $query->where('exercise_routine.day_number', $dayNumber);
                })
                ->first();
            
            $schedule[] = [
                'id' => $dayNumber,
                'day_name' => $days[$dayNumber - 1],
                'day_number' => $dayNumber,
                'workout_name' => $routine ? $routine->name : 'Atpūtas diena',
                'routine_id' => $routine ? $routine->id : null
            ];
        }
        
        return $schedule;
    }
}
