<?php

namespace App\Http\Controllers;

use App\Models\WorkoutLog;
use App\Models\PersonalRecord;
use App\Models\Goal;
use App\Models\Routine;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $activeWorkout = WorkoutSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['routine', 'exercises.exercise'])
            ->first();

        if ($activeWorkout) {
            return $this->showActiveWorkout($activeWorkout);
        }

        return $this->showNormalDashboard($user);
    }

    private function showActiveWorkout($activeWorkout)
    {
        return inertia('Dashboard', [
            'auth'             => ['user' => $activeWorkout->user],
            'hasActiveWorkout' => true,
            'activeRoutine'    => $this->getActiveRoutine($activeWorkout->user),
            'activeWorkout'    => [
                'id'           => $activeWorkout->id,
                'name'         => $activeWorkout->name,
                'routine_name' => $activeWorkout->routine?->name,
                'started_at'   => $activeWorkout->started_at,
                'time_elapsed' => $activeWorkout->started_at->diffInSeconds(now()),
                'exercises'    => $activeWorkout->exercises->map(fn($ex) => [
                    'id'             => $ex->exercise_id,
                    'name'           => $ex->exercise->name,
                    'sets_planned'   => $ex->sets_planned,
                    'reps_planned'   => $ex->reps_planned,
                    'sets_completed' => $ex->sets_completed,
                    'reps_completed' => $ex->reps_completed ?? [],
                    'weights_used'   => $ex->weights_used ?? [],
                ]),
            ],
            'stats'             => $this->getStats($activeWorkout->user),
            'todayWorkout'      => $this->getTodayWorkout($activeWorkout->user),
            'recentActivities'  => [],
            'weeklySchedule'    => $this->getWeeklySchedule($activeWorkout->user),
            'weeklyWeightStats' => $this->getWeeklyWeightStats($activeWorkout->user),
        ]);
    }

    private function showNormalDashboard($user)
    {
        return inertia('Dashboard', [
            'auth'              => ['user' => $user],
            'hasActiveWorkout'  => false,
            'activeRoutine'     => $this->getActiveRoutine($user),
            'stats'             => $this->getStats($user),
            'todayWorkout'      => $this->getTodayWorkout($user),
            'recentActivities'  => $this->getRecentActivities($user),
            'weeklySchedule'    => $this->getWeeklySchedule($user),
            'weeklyWeightStats' => $this->getWeeklyWeightStats($user),
        ]);
    }

    private function getStats($user): array
    {
        $currentStreak  = $this->calculateCurrentStreak($user);
        $weeklyWorkouts = WorkoutLog::where('user_id', $user->id)
            ->whereBetween('completed_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count();

        $totalWorkouts   = WorkoutLog::where('user_id', $user->id)->count();
        $totalDuration   = WorkoutLog::where('user_id', $user->id)->sum('duration_minutes');
        $personalRecords = PersonalRecord::where('user_id', $user->id)->count();
        $goalsAchieved   = Goal::where('user_id', $user->id)->where('completed', true)->count();

        $weeklyProgress = [];
        for ($i = 0; $i < 7; $i++) {
            $day    = Carbon::now()->startOfWeek()->addDays($i);
            $dayKey = strtolower($day->format('l'));
            $weeklyProgress[$dayKey] = WorkoutLog::where('user_id', $user->id)
                ->whereDate('completed_at', $day->format('Y-m-d'))
                ->exists() ? 100 : 0;
        }

        return [
            'currentStreak'   => $currentStreak,
            'weeklyWorkouts'  => $weeklyWorkouts,
            'totalWorkouts'   => $totalWorkouts,
            'totalDuration'   => $totalDuration,
            'goalsAchieved'   => $goalsAchieved,
            'personalRecords' => $personalRecords,
            'weeklyProgress'  => $weeklyProgress,
        ];
    }

    private function calculateCurrentStreak($user): int
    {
        $weeks = WorkoutLog::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->select(DB::raw('YEARWEEK(completed_at, 1) as week_number, MIN(completed_at) as week_start'))
            ->groupBy('week_number')
            ->orderBy('week_start', 'desc')
            ->get();

        if ($weeks->isEmpty()) return 0;

        $currentWeek = Carbon::parse($weeks[0]->week_start)->startOfWeek();
        if (Carbon::now()->startOfWeek()->diffInWeeks($currentWeek) > 1) return 0;

        $streak = 1;
        for ($i = 1; $i < $weeks->count(); $i++) {
            $nextWeek = Carbon::parse($weeks[$i]->week_start)->startOfWeek();
            if ($nextWeek->equalTo($currentWeek->copy()->subWeek())) {
                $streak++;
                $currentWeek = $nextWeek;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function getTodayWorkout($user): ?array
    {
        $dayNumber = Carbon::now()->dayOfWeekIso;

        $routine = Routine::with(['exercises' => fn($q) =>
                $q->where('exercise_routine.day_number', $dayNumber)])
            ->where('user_id', $user->id)
            ->whereHas('exercises', fn($q) =>
                $q->where('exercise_routine.day_number', $dayNumber))
            ->first();

        if (!$routine) return null;

        $duration = 0;
        foreach ($routine->exercises as $exercise) {
            $sets        = $exercise->pivot->sets ?? 3;
            $restSeconds = $exercise->pivot->rest_seconds ?? 60;
            $duration   += ($sets * 45) + ($sets * $restSeconds);
        }

        return [
            'id'        => $routine->id,
            'name'      => $routine->name,
            'duration'  => ceil($duration / 60) . ' min',
            'exercises' => $routine->exercises->map(fn($e) => [
                'id'           => $e->id,
                'name'         => $e->name,
                'sets'         => $e->pivot->sets ?? 3,
                'reps'         => $e->pivot->reps ?? 10,
                'rest_seconds' => $e->pivot->rest_seconds ?? 60,
            ])->toArray(),
        ];
    }

    private function getRecentActivities($user): array
    {
        $activities = [];

        foreach (WorkoutLog::with('routine')->where('user_id', $user->id)
            ->orderBy('completed_at', 'desc')->limit(3)->get() as $workout) {
            $activities[] = [
                'id'          => $workout->id,
                'type'        => 'workout',
                'title'       => 'Pabeigts: ' . ($workout->routine->name ?? 'Treniņš'),
                'description' => $workout->duration_minutes . ' min',
                'time'        => Carbon::parse($workout->completed_at)->diffForHumans(['parts' => 1]),
                'icon'        => '✅',
            ];
        }

        foreach (PersonalRecord::with('exercise')->where('user_id', $user->id)
            ->orderBy('achieved_at', 'desc')->limit(2)->get() as $pr) {
            $activities[] = [
                'id'          => $pr->id,
                'type'        => 'record',
                'title'       => 'Jauns PR: ' . ($pr->exercise->name ?? 'Vingrinājums'),
                'description' => $pr->weight . 'kg, ' . $pr->reps . ' atkārtojumi',
                'time'        => Carbon::parse($pr->achieved_at)->diffForHumans(['parts' => 1]),
                'icon'        => '🏆',
            ];
        }

        return array_slice($activities, 0, 3);
    }

    private function getActiveRoutine($user): ?array
    {
        $activeRoutine = $user->activeRoutine;
        if (!$activeRoutine) return null;

        $activeRoutine->load(['exercises' => fn($q) =>
            $q->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes'])
              ->orderBy('exercise_routine.day_number')
        ]);

        return [
            'id'              => $activeRoutine->id,
            'name'            => $activeRoutine->name,
            'description'     => $activeRoutine->description,
            'is_public'       => $activeRoutine->is_public,
            'user_id'         => $activeRoutine->user_id,
            'exercises_count' => $activeRoutine->exercises->count(),
            'exercises'       => $activeRoutine->exercises->map(fn($e) => [
                'id'           => $e->id,
                'name'         => $e->name,
                'muscle_group' => $e->muscle_group,
                'description'  => $e->description,
                'day_number'   => $e->pivot->day_number,
                'sets'         => $e->pivot->sets,
                'reps'         => $e->pivot->reps,
                'rest_seconds' => $e->pivot->rest_seconds,
                'notes'        => $e->pivot->notes,
                'pivot'        => [
                    'day_number'   => $e->pivot->day_number,
                    'sets'         => $e->pivot->sets,
                    'reps'         => $e->pivot->reps,
                    'rest_seconds' => $e->pivot->rest_seconds,
                    'notes'        => $e->pivot->notes,
                ],
            ])->toArray(),
        ];
    }

    private function getWeeklySchedule($user): array
    {
        $schedule      = [];
        $days          = ['Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena', 'Svētdiena'];
        $activeRoutine = $user->activeRoutine;

        if ($activeRoutine && !$activeRoutine->relationLoaded('exercises')) {
            $activeRoutine->load(['exercises' => fn($q) => $q->withPivot('day_number')]);
        }

        for ($dayNumber = 1; $dayNumber <= 7; $dayNumber++) {
            $routineForDay      = null;
            $isActiveRoutineDay = false;
            $workoutName        = 'Atpūtas diena';

            if ($activeRoutine) {
                $hasExercises = $activeRoutine->exercises
                    ->contains(fn($e) => $e->pivot->day_number == $dayNumber);

                if ($hasExercises) {
                    $routineForDay      = $activeRoutine;
                    $workoutName        = $activeRoutine->name;
                    $isActiveRoutineDay = true;
                }
            }

            $schedule[] = [
                'id'                => $dayNumber,
                'day_name'          => $days[$dayNumber - 1],
                'day_number'        => $dayNumber,
                'workout_name'      => $workoutName,
                'routine_id'        => $routineForDay?->id,
                'is_active_routine' => $isActiveRoutineDay,
                'has_workout'       => $routineForDay !== null,
                'is_rest_day'       => $routineForDay === null,
            ];
        }

        return $schedule;
    }

    private function getWeeklyWeightStats($user): array
    {
        $weekStart  = Carbon::now()->startOfWeek();
        $dayMapping = [
            'Monday' => 'Pirmdiena',   'Tuesday' => 'Otrdiena',
            'Wednesday' => 'Trešdiena', 'Thursday' => 'Ceturtdiena',
            'Friday' => 'Piektdiena',  'Saturday' => 'Sestdiena',
            'Sunday' => 'Svētdiena',
        ];

        $weightStats = array_fill_keys(array_values($dayMapping), ['totalWeight' => 0, 'exercises' => 0]);

        foreach (WorkoutLog::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $weekStart)
            ->get() as $log) {

            $latvianDay = $dayMapping[$log->completed_at->format('l')] ?? null;
            if (!$latvianDay) continue;

            foreach ($log->logExercises as $logExercise) {
                $weights = $logExercise->weights_used;
                if (!$weights || !is_array($weights)) continue;

                $hasWeight = false;
                foreach ($weights as $set) {
                    $val = is_numeric($set) ? floatval($set)
                        : (is_array($set) && isset($set['weight']) ? floatval($set['weight']) : 0);

                    if ($val > 0) {
                        $weightStats[$latvianDay]['totalWeight'] += $val;
                        $hasWeight = true;
                    }
                }
                if ($hasWeight) $weightStats[$latvianDay]['exercises']++;
            }
        }

        return $weightStats;
    }
}
