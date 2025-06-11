<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use App\Models\Exercise; 
use Illuminate\Http\Request;
use Inertia\Inertia;


class RoutineController extends Controller
{
   // app/Http/Controllers/RoutineController.php
public function show(Routine $routine)
{
    $routine->load(['exercises' => function ($query) {
        $query->orderBy('exercise_routine.day_number');
    }]);

    return inertia('Routines/Show', [
        'routine' => $routine,
        'weekDays' => [
            1 => ['id' => 1, 'name' => 'Monday'],
            2 => ['id' => 2, 'name' => 'Tuesday'],
            3 => ['id' => 3, 'name' => 'Wednesday'],
            4 => ['id' => 4, 'name' => 'Thursday'],
            5 => ['id' => 5, 'name' => 'Friday'],
            6 => ['id' => 6, 'name' => 'Saturday'],
            7 => ['id' => 7, 'name' => 'Sunday']
        ]
    ]);
}

public function create()
{
    return inertia('Routines/Create', [
        'exercises' => Exercise::all(),
        'weekDays' => [
            ['id' => 1, 'name' => 'Monday'],
            ['id' => 2, 'name' => 'Tuesday'],
            ['id' => 3, 'name' => 'Wednesday'],
            ['id' => 4, 'name' => 'Thursday'],
            ['id' => 5, 'name' => 'Friday'],
            ['id' => 6, 'name' => 'Saturday'],
            ['id' => 7, 'name' => 'Sunday']
        ]
    ]);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'is_public' => 'boolean',
        'exercises' => 'required|array|min:1',
        'exercises.*.id' => 'required|exists:exercises,id',
        'exercises.*.day_number' => 'required|integer|min:1|max:7',
        'exercises.*.sets' => 'required|integer|min:1',
        'exercises.*.reps' => 'required|integer|min:1',
        'exercises.*.rest_seconds' => 'nullable|integer|min:0',
        'exercises.*.notes' => 'nullable|string',
    ]);

    // Create routine through the user relationship
    $routine = $request->user()->routines()->create([
        'name' => $validated['name'],
        'description' => $validated['description'],
        'is_public' => $validated['is_public'] ?? false,
    ]);

    // Attach exercises
    $routine->exercises()->attach(
        collect($validated['exercises'])
            ->mapWithKeys(fn ($exercise) => [
                $exercise['id'] => [
                    'day_number' => $exercise['day_number'],
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'rest_seconds' => $exercise['rest_seconds'],
                    'notes' => $exercise['notes'],
                ]
            ])
    );

    return redirect()->route('routines.show', $routine);
}

public function index()
{
    $routines = Routine::with(['user', 'exercises'])
        ->latest()
        ->get();


     return Inertia::render('Routines/Routineview', [
        'routines' => $routines
    ]);

}

public function publicIndex()
{
    $routines = Routine::where('is_public', true)->get();
    return Inertia::render('Routines/Public', [
        'routines' => $routines
    ]);
}
}
