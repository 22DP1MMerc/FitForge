<?php
// routes/api.php

use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TodayWorkoutController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\RoutineController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Test route to verify API is working (no auth required)
Route::get('/ping', function() {
    return response()->json(['message' => 'API is working!']);
});

// Protected routes - using 'auth' middleware instead of 'auth:sanctum'
Route::middleware('auth')->group(function () {
    // Goals
    Route::apiResource('goals', GoalController::class)->except(['show']);
    
    // Dashboard
    Route::get('/dashboard-stats', [StatsController::class, 'getDashboardStats']);
    Route::get('/today-workout', [TodayWorkoutController::class, 'getTodayWorkout']);
    
    // Recent activities
    Route::get('/recent-activities', function () {
        $user = Auth::user();
        
        $activities = [];
        
        $recentWorkouts = $user->workoutLogs()
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
                    'icon' => '✅'
                ];
            }
        }
        
        return response()->json($activities);
    });
    
    // Workout routes
    Route::post('/workouts/start', function () {
        $user = Auth::user();
        
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:free,routine',
            'duration_minutes' => 'required|integer|min:1',
            'exercises' => 'required|array',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
        ]);
        
        $workoutLog = \App\Models\WorkoutLog::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'routine_id' => null,
            'duration_minutes' => $data['duration_minutes'],
            'calories_burned' => intval($data['duration_minutes'] * 5),
            'completed_at' => now()
        ]);

        foreach ($data['exercises'] as $exerciseData) {
            $workoutLog->exercises()->attach($exerciseData['exercise_id'], [
                'sets_completed' => $exerciseData['sets'] ?? 0,
                'reps_completed' => $exerciseData['reps'] ?? 0,
                'weight_used' => $exerciseData['weight'] ?? null
            ]);
        }
        
        return response()->json([
            'workout_id' => $workoutLog->id,
            'message' => 'Workout started successfully'
        ]);
    });

    Route::post('/workout-session/{id}/complete', function ($id) {
        try {
            $workoutSession = \App\Models\WorkoutSession::with('exercises')
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            
            $data = request()->validate([
                'duration_minutes' => 'required|integer|min:1',
                'calories_burned' => 'nullable|integer|min:0',
                'notes' => 'nullable|string'
            ]);
            
            if ($workoutSession->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Treniņš jau ir pabeigts vai atcelts'
                ], 400);
            }
            
            $workoutSession->update([
                'status' => 'completed',
                'ended_at' => now(),
                'duration_minutes' => $data['duration_minutes'],
                'calories_burned' => $data['calories_burned'] ?? 0,
                'notes' => $data['notes'] ?? null
            ]);
            
            $workoutLog = \App\Models\WorkoutLog::create([
                'user_id' => auth()->id(),
                'routine_id' => $workoutSession->routine_id,
                'name' => $workoutSession->name,
                'duration_minutes' => $data['duration_minutes'],
                'calories_burned' => $data['calories_burned'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'completed_at' => now()
            ]);
            
            foreach ($workoutSession->exercises as $sessionExercise) {
                if ($sessionExercise->sets_completed > 0) {
                    $workoutLog->exercises()->attach($sessionExercise->exercise_id, [
                        'sets_completed' => $sessionExercise->sets_completed,
                        'reps_completed' => !empty($sessionExercise->reps_completed) 
                            ? array_sum($sessionExercise->reps_completed) 
                            : 0,
                        'weight_used' => !empty($sessionExercise->weights_used)
                            ? array_sum($sessionExercise->weights_used) / count($sessionExercise->weights_used)
                            : 0
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Treniņš veiksmīgi pabeigts!',
                'workout_log_id' => $workoutLog->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Complete workout session error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Servera kļūda: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Routine routes
    Route::post('/routines/{routine}/set-active', [RoutineController::class, 'setActive']);
    Route::get('/routines/{routine}', [RoutineController::class, 'getRoutine']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/routines/set-active', [RoutineController::class, 'setActive']);
    Route::post('/routines/clear-active', [RoutineController::class, 'clearActive']);
    Route::get('/routines/active', [RoutineController::class, 'getActive']);
});
});
