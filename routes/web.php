<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkoutController;
use App\Models\Exercise;

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
        });
    } catch (\Exception $e) {
        $exercises = [];
    }
    
    return Inertia::render('Workout/FreeWorkout', [
        'availableExercises' => $exercises,
        'initialWorkout' => [
            'name' => 'Brīvais treniņš - ' . now()->format('d.m.Y'),
            'exercises' => []
        ]
    ]);
})->name('workout.free');

// API maršruts rutīnas vingrinājumu ielādei
Route::get('/api/routines/{routine}/exercises', function ($routineId) {
    try {
        $routine = \App\Models\Routine::findOrFail($routineId);
        
        // Pārbauda, vai rutīna pieder lietotājam vai ir publiska
        if ($routine->user_id !== auth()->id() && !$routine->is_public) {
            return response()->json(['error' => 'Nav piekļuves'], 403);
        }
        
        $exercises = $routine->exercises->map(function($exercise) {
            return [
                'id' => $exercise->id,
                'name' => $exercise->name,
                'muscle_group' => $exercise->muscle_group,
                'description' => $exercise->description,
                'sets' => $exercise->pivot->sets ?? 3,
                'reps' => $exercise->pivot->reps ?? 10,
                'rest_time' => $exercise->pivot->rest_time ?? 60,
            ];
        });
        
        return response()->json($exercises);
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Kļūda: ' . $e->getMessage()], 500);
    }
})->middleware('auth');

    // POST brīvā treniņa sākšanai (pagaidu - atgriež ziņojumu)
Route::post('/workout/start-free', function () {
    // Simulējam treniņa sākšanu
    return response()->json([
        'success' => true,
        'message' => 'Treniņš veiksmīgi sākts!',
        'redirect' => false // NEatgriežam redirect
    ]);
})->name('workout.start.free');
    
    // Workout dashboard (rāda aktīvo treniņu) - vienkārša versija
    Route::get('/workout', function () {
        try {
            $user = Auth::user();
            
            // Pārbauda vai ir aktīvs treniņš
            if (class_exists('App\Models\WorkoutSession')) {
                $activeWorkout = \App\Models\WorkoutSession::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();
                
                if ($activeWorkout) {
                    return redirect()->route('workout.active', $activeWorkout);
                }
            }
        } catch (\Exception $e) {
            // Ignorē kļūdu
        }
        
        return redirect()->route('dashboard');
    })->name('workout.dashboard');
    
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
        
        // Ja sesijā ir izvēlēta rutīna, ielādējam to
        $routine = null;
        if (session()->has('selected_routine')) {
            $routineId = session()->get('selected_routine');
            $routine = \App\Models\Routine::with('exercises')->find($routineId);
            session()->forget('selected_routine');
        }
        
        return Inertia::render('Workout/FreeWorkout', [
            'availableExercises' => $exercises,
            'initialWorkout' => [
                'name' => $routine ? $routine->name . ' - ' . now()->format('d.m.Y') : 'Brīvais treniņš - ' . now()->format('d.m.Y'),
                'exercises' => []
            ],
            'routine' => $routine ? [
                'id' => $routine->id,
                'name' => $routine->name,
                'description' => $routine->description,
                'exercises' => $routine->exercises->map(function($exercise) {
                    return [
                        'id' => $exercise->id,
                        'name' => $exercise->name,
                        'muscle_group' => $exercise->muscle_group,
                        'sets' => $exercise->pivot->sets ?? 3,
                        'reps' => $exercise->pivot->reps ?? 10,
                        'rest_time' => $exercise->pivot->rest_time ?? 60,
                    ];
                })
            ] : null
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Free workout error: ' . $e->getMessage());
        $exercises = [];
    }
    
    return Inertia::render('Workout/FreeWorkout', [
        'availableExercises' => $exercises,
        'initialWorkout' => [
            'name' => 'Brīvais treniņš - ' . now()->format('d.m.Y'),
            'exercises' => []
        ],
        'routine' => null
    ]);
})->name('workout.free');

// Maršruts rutīnas sākšanai (tikai saglabā rutīnu sesijā)
Route::get('/workout/start/{routine}', function ($routineId) {
    try {
        $routine = \App\Models\Routine::findOrFail($routineId);
        
        // Pārbauda, vai rutīna pieder lietotājam vai ir publiska
        if ($routine->user_id !== auth()->id() && !$routine->is_public) {
            return redirect()->route('workout.free')->with('error', 'Nav piekļuves šai rutīnai');
        }
        
        // Saglabā izvēlēto rutīnu sesijā
        session(['selected_routine' => $routine->id]);
        
        return redirect()->route('workout.free')->with('success', 'Rutīna ielādēta!');
        
    } catch (\Exception $e) {
        return redirect()->route('workout.free')->with('error', 'Kļūda: ' . $e->getMessage());
    }
})->name('workout.start.routine');
    
    // Aktīvā treniņa lapa - vienkārša versija
    Route::get('/workout/{workoutSession}/active', function ($workoutSessionId) {
        try {
            if (class_exists('App\Models\WorkoutSession')) {
                $session = \App\Models\WorkoutSession::find($workoutSessionId);
                
                if (!$session) {
                    return redirect()->route('dashboard')->with('error', 'Treniņa sesija nav atrasta');
                }
                
                return Inertia::render('Workout/Active', [
                    'session' => $session,
                    'message' => 'Aktīvā treniņa funkcionalitāte tiek izstrādāta'
                ]);
            } else {
                return redirect()->route('dashboard')->with('error', 'WorkoutSession modelis nav pieejams');
            }
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Kļūda: ' . $e->getMessage());
        }
    })->name('workout.active');
    
    // Treniņa pabeigšana - vienkārša versija
    Route::post('/workout/{workoutSession}/complete', function ($workoutSessionId) {
        try {
            if (class_exists('App\Models\WorkoutSession')) {
                $session = \App\Models\WorkoutSession::find($workoutSessionId);
                
                if ($session) {
                    $session->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Ignorē kļūdu
        }
        
        return redirect()->route('dashboard')->with('success', 'Treniņš veiksmīgi pabeigts!');
    })->name('workout.complete');
    
    // Treniņa atcelšana - vienkārša versija
    Route::post('/workout/{workoutSession}/cancel', function ($workoutSessionId) {
        try {
            if (class_exists('App\Models\WorkoutSession')) {
                $session = \App\Models\WorkoutSession::find($workoutSessionId);
                
                if ($session) {
                    $session->update([
                        'status' => 'cancelled',
                        'completed_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Ignorē kļūdu
        }
        
        return redirect()->route('dashboard')->with('info', 'Treniņš atcelts');
    })->name('workout.cancel');
});

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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
