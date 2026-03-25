<?php

namespace App\Providers;

use App\Models\WorkoutLogExercise;
use App\Observers\WorkoutLogExerciseObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        WorkoutLogExercise::observe(WorkoutLogExerciseObserver::class);
    }
}
