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
});
