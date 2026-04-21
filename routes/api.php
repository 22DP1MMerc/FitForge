<?php

use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TodayWorkoutController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\RoutineController;
use App\Models\WorkoutLog;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Pārbauda vai API strādā
Route::get('/ping', fn() => response()->json(['message' => 'API is working!']));

Route::middleware('auth')->group(function () {

    // Mērķi
    Route::apiResource('goals', GoalController::class)->except(['show']);

    // Dashboard statistika
    Route::get('/dashboard-stats', [StatsController::class, 'getDashboardStats']);
    Route::get('/today-workout', [TodayWorkoutController::class, 'getTodayWorkout']);

    // Iegūst aktīvās sesijas datus
Route::get('/workout-session/{id}', function ($id) {
    $session = \App\Models\WorkoutSession::with(['exercises.exercise'])
        ->where('id', $id)
        ->where('user_id', Auth::id())
        ->first();

    if (!$session) {
        return response()->json(['error' => 'Sesija nav atrasta'], 404);
    }

    return response()->json([
        'id' => $session->id,
        'name' => $session->name,
        'status' => $session->status,
        'started_at' => $session->started_at,
        'duration_minutes' => $session->duration_minutes,
        'exercises' => $session->exercises->map(fn($ex) => [
            'id' => $ex->exercise_id,
            'session_exercise_id' => $ex->id,
            'name' => $ex->exercise->name,
            'muscle_group' => $ex->exercise->muscle_group,
            'type'                => $ex->exercise->type ?? 'strength',
            'sets_planned' => $ex->sets_planned,
            'reps_planned' => $ex->reps_planned,
            'sets_completed' => $ex->sets_completed,
            'reps_completed' => $ex->reps_completed ?? [],
            'weights_used' => $ex->weights_used ?? [],
            'durations_completed' => $ex->durations_completed ?? [],
        ]),
    ]);
});

    // Pēdējās aktivitātes
    Route::get('/recent-activities', function () {
        $activities = [];

        $recentWorkouts = Auth::user()
            ->workoutLogs()
            ->with('routine')
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentWorkouts as $workout) {
            if ($workout->routine) {
                $activities[] = [
                    'type' => 'workout',
                    'title' => 'Pabeigts: ' . $workout->routine->name,
                    'time' => $workout->completed_at->diffForHumans(),
                    'icon' => '✅',
                ];
            }
        }

        return response()->json($activities);
    });

    // Sākt treniņu
    Route::post('/workouts/start', function () {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:free,routine',
            'duration_minutes' => 'required|integer|min:1',
            'exercises' => 'required|array',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
        ]);

        $workoutLog = WorkoutLog::create([
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'routine_id' => null,
            'duration_minutes' => $data['duration_minutes'],
            'completed_at' => now(),
        ]);

        foreach ($data['exercises'] as $exerciseData) {
            $workoutLog->exercises()->attach($exerciseData['exercise_id'], [
                'sets_completed' => $exerciseData['sets'] ?? 0,
                'reps_completed' => $exerciseData['reps'] ?? 0,
                'weight_used' => $exerciseData['weight'] ?? null,
            ]);
        }

        return response()->json([
            'workout_id' => $workoutLog->id,
            'message' => 'Workout started successfully',
        ]);
    });

    // Pabeigt aktīvo sesiju
    Route::post('/workout-session/{id}/complete', function ($id) {
        try {
            $session = WorkoutSession::with('exercises')
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $data = request()->validate([
                'duration_minutes' => 'required|integer|min:1',
                'notes' => 'nullable|string',
            ]);

            if ($session->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Treniņš jau ir pabeigts vai atcelts',
                ], 400);
            }

            $session->update([
                'status' => 'completed',
                'ended_at' => now(),
                'duration_minutes' => $data['duration_minutes'],
                'notes' => $data['notes'] ?? null,
            ]);

            $workoutLog = WorkoutLog::create([
                'user_id' => Auth::id(),
                'routine_id' => $session->routine_id,
                'name' => $session->name,
                'duration_minutes' => $data['duration_minutes'],
                'notes' => $data['notes'] ?? null,
                'completed_at' => now(),
            ]);

            foreach ($session->exercises as $ex) {
                if ($ex->sets_completed > 0) {
                    $workoutLog->exercises()->attach($ex->exercise_id, [
                        'sets_completed' => $ex->sets_completed,
                        'reps_completed' => !empty($ex->reps_completed)
                            ? array_sum($ex->reps_completed) : 0,
                        'weight_used' => !empty($ex->weights_used)
                            ? array_sum($ex->weights_used) / count($ex->weights_used) : 0,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Treniņš veiksmīgi pabeigts!',
                'workout_log_id' => $workoutLog->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Complete workout session error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Servera kļūda'], 500);
        }
    });

    // Rutīnu API
    Route::post('/routines/{routine}/set-active', [RoutineController::class, 'setActive']);
    Route::post('/routines/clear-active', [RoutineController::class, 'clearActive']);
    Route::get('/routines/{routine}', [RoutineController::class, 'getRoutine']);
});
