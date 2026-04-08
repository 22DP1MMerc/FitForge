<?php

namespace App\Http\Controllers;

use App\Models\WorkoutLog;
use App\Models\PersonalRecord;
use App\Models\Goal;
use App\Models\Routine;
use App\Models\WorkoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        
        // Iegūst svaru statistiku
        $weeklyWeightStats = $this->getWeeklyWeightStats($activeWorkout->user);
        
        return inertia('Dashboard', [
            'auth' => [
                'user' => $activeWorkout->user
            ],
            'hasActiveWorkout' => true,
            'activeRoutine' => $this->getActiveRoutine($activeWorkout->user), // ✅ Pievienojam
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
            'stats' => $this->getStats($activeWorkout->user),
            'todayWorkout' => $this->getTodayWorkout($activeWorkout->user),
            'recentActivities' => [],
            'weeklySchedule' => $this->getWeeklySchedule($activeWorkout->user),
            'weeklyWeightStats' => $weeklyWeightStats
        ]);
    }

    private function showNormalDashboard($user)
    {
        // Pašreizējā sērija
        $currentStreak = $this->calculateCurrentStreak($user);
        
        // Šonedēļas treniņi
        $weeklyWorkouts = WorkoutLog::where('user_id', $user->id)
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->count();
        
        // Kopējais treniņu skaits
        $totalWorkouts = WorkoutLog::where('user_id', $user->id)->count();
        
        // Kopējais laiks
        $totalDuration = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        
        // Kalorijas
        $caloriesBurned = WorkoutLog::where('user_id', $user->id)->sum('calories_burned') ?? 0;
        
        // Personīgie rekordi
        $personalRecords = PersonalRecord::where('user_id', $user->id)->count();
        
        // Sasniegtie mērķi
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
        
        // Aktīvā rutīna no sesijas
        $activeRoutine = $this->getActiveRoutine($user);
        
        // Šodienas treniņš
        $todayWorkout = $this->getTodayWorkout($user);
        
        // Nesenie notikumi
        $recentActivities = $this->getRecentActivities($user);
        
        // Nedēļas grafiks
        $weeklySchedule = $this->getWeeklySchedule($user);
        
        // Iegūst svaru statistiku
        $weeklyWeightStats = $this->getWeeklyWeightStats($user);
        
        return inertia('Dashboard', [
            'auth' => [
                'user' => $user
            ],
            'hasActiveWorkout' => false,
            'activeRoutine' => $activeRoutine,
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
    // Get all weeks with at least one workout, ordered by week starting from most recent
    $weeksWithWorkouts = WorkoutLog::where('user_id', $user->id)
        ->whereNotNull('completed_at')
        ->select(DB::raw('YEARWEEK(completed_at, 1) as week_number, MIN(completed_at) as week_start'))
        ->groupBy('week_number')
        ->orderBy('week_start', 'desc')
        ->get();
    
    if ($weeksWithWorkouts->isEmpty()) {
        return 0;
    }
    
    $streak = 1; // Start with 1 for the most recent week that has workouts
    $currentWeek = Carbon::parse($weeksWithWorkouts[0]->week_start)->startOfWeek();
    
    // Check if the most recent week with workout is current week or last week
    $today = Carbon::now()->startOfWeek();
    $weekDiff = $today->diffInWeeks($currentWeek);
    
    // If the most recent workout week is older than last week, no streak
    if ($weekDiff > 1) {
        return 0;
    }
    
    // Check consecutive weeks going backwards
    for ($i = 1; $i < $weeksWithWorkouts->count(); $i++) {
        $nextWeek = Carbon::parse($weeksWithWorkouts[$i]->week_start)->startOfWeek();
        $expectedPreviousWeek = $currentWeek->copy()->subWeek();
        
        if ($nextWeek->equalTo($expectedPreviousWeek)) {
            $streak++;
            $currentWeek = $nextWeek;
        } else {
            break;
        }
    }
    
    return $streak;
}
    
    private function getTodayWorkout($user)
    {
        $today = Carbon::now();
        $dayNumber = $today->dayOfWeekIso;
        
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

    private function getActiveRoutine($user)
{
    // Get active routine from user model instead of session
    $activeRoutine = $user->activeRoutine;
    
    \Log::info('DashboardController: Checking active routine - ID: ' . ($activeRoutine ? $activeRoutine->id : 'null'));
    
    if ($activeRoutine) {
        // Load the exercises relationship
        $activeRoutine->load(['exercises' => function($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes'])
                  ->orderBy('exercise_routine.day_number');
        }]);
        
        \Log::info('DashboardController: Found active routine - Name: ' . $activeRoutine->name);
        
        // Format the data for frontend compatibility
        $formattedRoutine = [
            'id' => $activeRoutine->id,
            'name' => $activeRoutine->name,
            'description' => $activeRoutine->description,
            'is_public' => $activeRoutine->is_public,
            'user_id' => $activeRoutine->user_id,
            'exercises_count' => $activeRoutine->exercises->count(),
            'exercises' => $activeRoutine->exercises->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'muscle_group' => $exercise->muscle_group,
                    'description' => $exercise->description,
                    'day_number' => $exercise->pivot->day_number,
                    'sets' => $exercise->pivot->sets,
                    'reps' => $exercise->pivot->reps,
                    'rest_seconds' => $exercise->pivot->rest_seconds,
                    'notes' => $exercise->pivot->notes,
                    'pivot' => [
                        'day_number' => $exercise->pivot->day_number,
                        'sets' => $exercise->pivot->sets,
                        'reps' => $exercise->pivot->reps,
                        'rest_seconds' => $exercise->pivot->rest_seconds,
                        'notes' => $exercise->pivot->notes
                    ]
                ];
            })->toArray()
        ];
        
        \Log::info('DashboardController: Formatted routine exercises count: ' . count($formattedRoutine['exercises']));
        return $formattedRoutine;
    } else {
        \Log::info('DashboardController: No active routine found for user');
    }
    
    return null;
}

    private function getWeeklySchedule($user)
{
    $schedule = [];
    $days = ['Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena', 'Svētdiena'];
    
    // 1. Get user's active routine from database
    $activeRoutine = $user->activeRoutine;
    
    if ($activeRoutine) {
        // Load exercises if not already loaded
        if (!$activeRoutine->relationLoaded('exercises')) {
            $activeRoutine->load(['exercises' => function($query) {
                $query->withPivot('day_number');
            }]);
        }
    }
    
    // 2. User's and public routines
    $allRoutines = Routine::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('is_public', true);
        })
        ->with(['exercises' => function($query) {
            $query->withPivot('day_number');
        }])
        ->get();
    
    for ($dayNumber = 1; $dayNumber <= 7; $dayNumber++) {
        $routineForDay = null;
        $isActiveRoutineDay = false;
        $workoutName = 'Atpūtas diena';
        
        // A. First priority: active routine
        if ($activeRoutine) {
            $hasExercisesForDay = $activeRoutine->exercises
                ->contains(function($exercise) use ($dayNumber) {
                    return $exercise->pivot->day_number == $dayNumber;
                });
            
            if ($hasExercisesForDay) {
                $routineForDay = $activeRoutine;
                $workoutName = $activeRoutine->name;
                $isActiveRoutineDay = true;
            }
        }
        
        $schedule[] = [
            'id' => $dayNumber,
            'day_name' => $days[$dayNumber - 1],
            'day_number' => $dayNumber,
            'workout_name' => $workoutName,
            'routine_id' => $routineForDay ? $routineForDay->id : null,
            'is_active_routine' => $isActiveRoutineDay,
            'has_workout' => $routineForDay !== null,
            'is_rest_day' => $routineForDay === null
        ];
    }
    
    return $schedule;
}

    private function getWeeklyWeightStats($user)
    {
        // Izmantojam tieši WorkoutLog totalWeight atribūtu
        $weekStart = Carbon::now()->startOfWeek();
        
        $workoutLogs = WorkoutLog::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $weekStart)
            ->get();
        
        $dayMapping = [
            'Monday' => 'Pirmdiena',
            'Tuesday' => 'Otrdiena', 
            'Wednesday' => 'Trešdiena',
            'Thursday' => 'Ceturtdiena',
            'Friday' => 'Piektdiena',
            'Saturday' => 'Sestdiena',
            'Sunday' => 'Svētdiena'
        ];
        
        $weightStats = [];
        foreach ($dayMapping as $lat) {
            $weightStats[$lat] = ['totalWeight' => 0, 'exercises' => 0];
        }
        
        foreach ($workoutLogs as $log) {
            $englishDay = $log->completed_at->format('l');
            $latvianDay = $dayMapping[$englishDay] ?? null;
            
            if (!$latvianDay) continue;
            
            $totalWeight = 0;
            $exercisesCount = 0;
            
            // Saskaitam svarus no katra logExercise
            foreach ($log->logExercises as $logExercise) {
                $weights = $logExercise->weights_used;
                if (!$weights || !is_array($weights)) continue;
                
                $hasWeight = false;
                foreach ($weights as $set) {
                    $weightValue = 0;
                    
                    if (is_numeric($set)) {
                        $weightValue = floatval($set);
                    } elseif (is_array($set) && isset($set['weight'])) {
                        $weightValue = floatval($set['weight']);
                    }
                    
                    if ($weightValue > 0) {
                        $totalWeight += $weightValue;
                        $hasWeight = true;
                    }
                }
                
                if ($hasWeight) {
                    $exercisesCount++;
                }
            }
            
            if ($totalWeight > 0) {
                $weightStats[$latvianDay]['totalWeight'] += $totalWeight;
                $weightStats[$latvianDay]['exercises'] += $exercisesCount;
            }
        }
        
        return $weightStats;
    }
    
    private function calculateSessionWeight($session)
    {
        $totalWeight = 0;
        
        $session->load(['exercises' => function($query) {
            $query->with('exercise');
        }]);
        
        foreach ($session->exercises as $sessionExercise) {
            $weights = $sessionExercise->weights_used;
            
            if (empty($weights) || !is_array($weights)) {
                continue;
            }
            
            foreach ($weights as $set) {
                if (is_array($set) && isset($set['weight'])) {
                    $totalWeight += floatval($set['weight']);
                } else if (is_numeric($set)) {
                    $totalWeight += floatval($set);
                } else if (is_string($set)) {
                    // Mēģinām iegūt skaitli no stringa
                    preg_match('/\d+(\.\d+)?/', $set, $matches);
                    if (!empty($matches)) {
                        $totalWeight += floatval($matches[0]);
                    }
                }
            }
        }
        
        return $totalWeight;
    }
}
