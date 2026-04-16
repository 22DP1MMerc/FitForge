<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutLogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Sākumlapa
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('HomeView');
})->name('home');

// Vingrinājumi
Route::resource('/exercises', ExerciseController::class);
Route::get('/api/exercises/filter', [ExerciseController::class, 'filter']);

// Mērķu API
Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::put('/goals/{goal}', [GoalController::class, 'update']);
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy']);
});

// Treniņu maršruti
Route::middleware('auth')->group(function () {
    Route::get('/workout/free', [WorkoutController::class, 'freeWorkout'])->name('workout.free');
    Route::post('/workout/free/start', [WorkoutController::class, 'startFreeWorkout'])->name('workout.free.start');
    Route::get('/workout/{workoutSession}', [WorkoutController::class, 'active'])->name('workout.active');
    Route::post('/workout/{workoutSession}/complete', [WorkoutController::class, 'completeWorkout'])->name('workout.complete');
    Route::post('/workout/{workoutSession}/cancel', [WorkoutController::class, 'cancelWorkout'])->name('workout.cancel');
    Route::post('/workout/{workoutSession}/exercises', [WorkoutController::class, 'addExercise'])->name('workout.add-exercise');
    Route::post('/workout/{workoutSession}/exercises/{exercise}/set', [WorkoutController::class, 'updateSet'])->name('workout.update-set');
    Route::delete('/workout/{workoutSession}/exercises/{exercise}', [WorkoutController::class, 'removeExercise'])->name('workout.remove-exercise');
});

// Rutīnas
Route::middleware('auth')->group(function () {
    Route::get('/routines/create', [RoutineController::class, 'create'])->name('routines.create');
    Route::get('/routines/my', [RoutineController::class, 'myRoutines'])->name('routines.my');
    Route::get('/routines/public', [RoutineController::class, 'publicIndex'])->name('routines.public');
    Route::post('/routines/{routine}/set-active', [RoutineController::class, 'setActive'])->name('routines.set-active');
    Route::get('/routines/{routine}/edit', [RoutineController::class, 'edit'])->name('routines.edit');
    Route::put('/routines/{routine}', [RoutineController::class, 'update'])->name('routines.update');
    Route::get('/routines/{routine}/data', [RoutineController::class, 'getActiveRoutineData'])->name('routines.data');
    Route::post('/api/routines/clear-active', [RoutineController::class, 'clearActive'])->name('routines.clear-active');
});

Route::resource('routines', RoutineController::class)
    ->only(['index', 'store', 'show'])
    ->names([
        'index' => 'routines.index',
        'store' => 'routines.store',
        'show' => 'routines.show',
    ]);

// Dashboard
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Profils
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Treniņu žurnāls
Route::middleware('auth')->prefix('workout-logs')->group(function () {
    Route::get('/', [WorkoutLogController::class, 'index'])->name('workout-logs.index');
    Route::get('/{workoutLog}', [WorkoutLogController::class, 'show'])->name('workout-logs.show');
    Route::delete('/{workoutLog}', [WorkoutLogController::class, 'destroy'])->name('workout-logs.destroy');
});

// Admin panelis
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
