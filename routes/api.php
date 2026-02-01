<?php

use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TodayWorkoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Dashboard statistika
    Route::get('/dashboard-stats', [StatsController::class, 'getDashboardStats']);
    
    // Šodienas treniņš
    Route::get('/today-workout', [TodayWorkoutController::class, 'getTodayWorkout']);
    
    // Nesenie notikumi
    Route::get('/recent-activities', function () {
        $user = Auth::user();
        
        $activities = [];
        
        // Pēdējie 3 treniņi
        $recentWorkouts = $user->workoutLogs()
            ->with('routine')
            ->orderBy('completed_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentWorkouts as $workout) {
            $activities[] = [
                'type' => 'workout',
                'title' => 'Pabeigts: ' . $workout->routine->name,
                'time' => $workout->completed_at->diffForHumans(),
                'icon' => '✅'
            ];
        }
        
        return response()->json($activities);
    });
    
     Route::post('/workouts/start', function () {
        $user = Auth::user();
        
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:free,routine',
            'duration_minutes' => 'required|integer|min:1',
            'exercises' => 'required|array',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
        ]);
        
        // Izveido workout log ierakstu
        $workoutLog = \App\Models\WorkoutLog::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'routine_id' => null, // Brīvajam treniņam nav rutīnas
            'duration_minutes' => $data['duration_minutes'],
            'calories_burned' => intval($data['duration_minutes'] * 5), // Aptuvenais aprēķins
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
            
            // Validācija
            $data = request()->validate([
                'duration_minutes' => 'required|integer|min:1',
                'calories_burned' => 'nullable|integer|min:0',
                'notes' => 'nullable|string'
            ]);
            
            // Pārbauda, vai sesija ir aktīva
            if ($workoutSession->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Treniņš jau ir pabeigts vai atcelts'
                ], 400);
            }
            
            // Atjaunina sesiju
            $workoutSession->update([
                'status' => 'completed',
                'ended_at' => now(),
                'duration_minutes' => $data['duration_minutes'],
                'calories_burned' => $data['calories_burned'] ?? 0,
                'notes' => $data['notes'] ?? null
            ]);
            
            // Izveido treniņa logu
            $workoutLog = \App\Models\WorkoutLog::create([
                'user_id' => auth()->id(),
                'routine_id' => $workoutSession->routine_id,
                'name' => $workoutSession->name,
                'duration_minutes' => $data['duration_minutes'],
                'calories_burned' => $data['calories_burned'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'completed_at' => now()
            ]);
            
            // Pārnes vingrinājumus no sesijas uz logu
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
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Treniņa sesija nav atrasta'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Complete workout session error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Servera kļūda: ' . $e->getMessage()
            ], 500);
        }
    });
});
    
 Route::post('/workouts/{workoutLog}/complete', function ($workoutLogId) {
        $workoutLog = \App\Models\WorkoutLog::findOrFail($workoutLogId);
        
        // Pārbauda, vai treniņš pieder lietotājam
        if ($workoutLog->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $data = request()->validate([
            'duration' => 'required|integer',
            'exercises' => 'required|array',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
        ]);
        
        $workoutLog->update([
            'duration_minutes' => $data['duration'],
            'calories_burned' => intval($data['duration'] * 5) // 5 kalorijas/minūtē
        ]);
         // Atjaunina vingrinājumu datus
        foreach ($data['exercises'] as $exerciseData) {
            $workoutLog->exercises()->updateExistingPivot($exerciseData['exercise_id'], [
                'sets_completed' => $exerciseData['sets'] ?? 0,
                'reps_completed' => $exerciseData['reps'] ?? 0,
                'weight_used' => $exerciseData['weight'] ?? null
            ]);
        }
        
        return response()->json([
            'message' => 'Workout completed successfully!'
        ]);
    });

Route::post('/routines/{routine}/set-active', [RoutineController::class, 'setActive']);

Route::get('/api/routines/{routine}', [RoutineController::class, 'getRoutine']);

