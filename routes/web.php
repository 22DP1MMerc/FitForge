<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\RoutineController;



Route::get('/', function () {
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

Route::middleware('auth')->group(function () {
    // Make sure this comes before any /routines/{routine} routes
    Route::get('/routines/create', [RoutineController::class, 'create'])
        ->name('routines.create');});
        
Route::resource('routines', RoutineController::class)
    ->only(['index', 'store', 'show']) // Add `store` and optionally `show`
    ->names([
        'index' => 'routines',
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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
