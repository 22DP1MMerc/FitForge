<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutLogController;
use App\Models\Exercise;
use App\Http\Controllers\Settings\ProfileController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('HomeView');
})->name('home');

Route::get('/about', function () {
    return Inertia::render('AboutView');
})->name('about');

Route::get('/contacts', function () {
    return Inertia::render('ContactView');
})->name('contact');

Route::resource('/exercises', ExerciseController::class);

Route::prefix('api')->group(function () {
    Route::get('/exercises/filter', [ExerciseController::class, 'filter']);
    Route::post('/exercises', [ExerciseController::class, 'store']);
});

// Workout routes
Route::middleware(['auth'])->group(function () {
    // POST brīvā treniņa sākšanai
    Route::post('/workout/start-free', function () {
        return response()->json([
            'success' => true,
            'message' => 'Treniņš veiksmīgi sākts!',
            'redirect' => false
        ]);
    })->name('workout.start.free');
    
    // GET: Brīvais treniņš
    Route::get('/workout/free', function () {
        try {
            $exercises = \App\Models\Exercise::all()->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'muscle_group' => $exercise->muscle_group,
                    'description' => $exercise->description,
                    'equipment' => $exercise->equipment,
                ];
            })->toArray();

            // Pārbaudām, vai jau ir aktīvs treniņš
            $activeWorkout = \App\Models\WorkoutSession::where('user_id', auth()->id())
                ->where('status', 'active')
                ->first();

            return Inertia::render('Workout/FreeWorkout', [
                'availableExercises' => $exercises,
                'initialWorkout' => [
                    'name' => 'Brīvais treniņš - ' . now()->format('d.m.Y'),
                    'exercises' => []
                ],
                'workoutSession' => $activeWorkout ? [
                    'id' => $activeWorkout->id,
                    'name' => $activeWorkout->name,
                    'status' => $activeWorkout->status,
                    'started_at' => $activeWorkout->started_at->toISOString()
                ] : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Free workout error: ' . $e->getMessage());
            return Inertia::render('Workout/FreeWorkout', [
                'availableExercises' => [],
                'initialWorkout' => [
                    'name' => 'Brīvais treniņš',
                    'exercises' => []
                ],
                'workoutSession' => null
            ]);
        }
    })->name('workout.free');

    // POST: Sākt brīvo treniņu
    Route::post('/workout/free/start', [WorkoutController::class, 'startFreeWorkout'])->name('workout.free.start');
    
    // POST: Pabeigt treniņu
    Route::post('/workout/{workoutSession}/complete', [WorkoutController::class, 'completeWorkout'])->name('workout.complete');
    
    // POST: Atcelt treniņu
    Route::post('/workout/{workoutSession}/cancel', [WorkoutController::class, 'cancelWorkout'])->name('workout.cancel');
    
    // GET: Aktīvā treniņa lapa
    Route::get('/workout/{workoutSession}', function ($workoutSessionId) {
        try {
            $workoutSession = \App\Models\WorkoutSession::with(['exercises.exercise', 'routine'])
                ->where('id', $workoutSessionId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            
            if ($workoutSession->status !== 'active') {
                return redirect()->route('dashboard')->with('error', 'Treniņš nav aktīvs');
            }
            
            $exercises = $workoutSession->exercises->map(function($sessionExercise) {
                return [
                    'id' => $sessionExercise->exercise_id,
                    'session_exercise_id' => $sessionExercise->id,
                    'name' => $sessionExercise->exercise->name,
                    'muscle_group' => $sessionExercise->exercise->muscle_group,
                    'sets' => $sessionExercise->sets_planned,
                    'reps' => $sessionExercise->reps_planned,
                    'rest_seconds' => $sessionExercise->rest_time ?? 60,
                    'sets_completed' => $sessionExercise->sets_completed,
                    'reps_completed' => $sessionExercise->reps_completed ?? [],
                    'weights_used' => $sessionExercise->weights_used ?? []
                ];
            });
            
            return Inertia::render('Workout/Active', [
                'workoutSession' => [
                    'id' => $workoutSession->id,
                    'name' => $workoutSession->name,
                    'type' => $workoutSession->type,
                    'status' => $workoutSession->status,
                    'started_at' => $workoutSession->started_at->toISOString()
                ],
                'routine' => $workoutSession->routine ? [
                    'id' => $workoutSession->routine->id,
                    'name' => $workoutSession->routine->name,
                    'description' => $workoutSession->routine->description
                ] : null,
                'exercises' => $exercises,
                'started_at' => $workoutSession->started_at->toISOString()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Workout active error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Treniņa sesija nav atrasta');
        }
    })->name('workout.active');
    
    // POST: Pievienot vingrinājumu sesijai
    Route::post('/workout/{workoutSession}/exercises', [WorkoutController::class, 'addExercise'])->name('workout.add-exercise');
    
    // POST: Atjaunināt setu
    Route::post('/workout/{workoutSession}/exercises/{exercise}/set', [WorkoutController::class, 'updateSet'])
        ->name('workout.update-set');
    
    // DELETE: Noņemt vingrinājumu
    Route::delete('/workout/{workoutSession}/exercises/{exercise}', [WorkoutController::class, 'removeExercise'])->name('workout.remove-exercise');
});

// API workout session
Route::get('/api/workout-session/{workoutSession}', function ($workoutSessionId) {
    try {
        $workoutSession = \App\Models\WorkoutSession::with(['exercises.exercise'])
            ->where('id', $workoutSessionId)
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$workoutSession) {
            return response()->json([
                'error' => 'Treniņa sesija nav atrasta'
            ], 404);
        }
        
        return response()->json([
            'id' => $workoutSession->id,
            'name' => $workoutSession->name,
            'status' => $workoutSession->status,
            'started_at' => $workoutSession->started_at,
            'duration_minutes' => $workoutSession->duration_minutes,
            'exercises' => $workoutSession->exercises->map(function($sessionExercise) {
                return [
                    'id' => $sessionExercise->exercise_id,
                    'session_exercise_id' => $sessionExercise->id,
                    'name' => $sessionExercise->exercise->name,
                    'muscle_group' => $sessionExercise->exercise->muscle_group,
                    'sets_planned' => $sessionExercise->sets_planned,
                    'reps_planned' => $sessionExercise->reps_planned,
                    'sets_completed' => $sessionExercise->sets_completed,
                    'reps_completed' => $sessionExercise->reps_completed ?? [],
                    'weights_used' => $sessionExercise->weights_used ?? [],
                ];
            })
        ]);
        
    } catch (\Exception $e) {
        \Log::error('API workout session error: ' . $e->getMessage());
        return response()->json([
            'error' => 'Servera kļūda',
            'message' => $e->getMessage()
        ], 500);
    }
})->middleware('auth');

// Rutīnu maršruti
Route::middleware('auth')->group(function () {
    Route::get('/routines/create', [RoutineController::class, 'create'])
        ->name('routines.create');
});

Route::resource('routines', RoutineController::class)
    ->only(['index', 'store', 'show'])
    ->names([
        'index' => 'routines.index',
        'store' => 'routines.store',
        'show' => 'routines.show',
    ]);

// Custom public route
Route::get('/routines/public', [RoutineController::class, 'publicIndex'])
    ->name('routines.public')
    ->middleware('auth');

Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Register');
})->name('register');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('workout-logs')->group(function () {
        Route::get('/', [WorkoutLogController::class, 'index'])->name('workout-logs.index');
        Route::get('/{workoutLog}', [WorkoutLogController::class, 'show'])->name('workout-logs.show');
        Route::delete('/{workoutLog}', [WorkoutLogController::class, 'destroy'])->name('workout-logs.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
