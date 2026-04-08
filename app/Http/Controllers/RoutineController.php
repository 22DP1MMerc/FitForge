<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use App\Models\Exercise; 
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class RoutineController extends Controller
{
    public function show(Routine $routine)
    {
        // Add authorization check here too
        $user = auth()->user();
        
        // Check if routine is accessible
        if (!$routine->is_public && (!$user || $routine->user_id !== $user->id)) {
            abort(403, 'Unauthorized access to this routine.');
        }
        
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
        $user = auth()->user();
        
        if ($user) {
            // Logged in users: public routines + their own routines
            $routines = Routine::with(['user', 'exercises'])
                ->where(function($query) use ($user) {
                    $query->where('is_public', true)
                          ->orWhere('user_id', $user->id);
                })
                ->latest()
                ->get();
        } else {
            // Guests: only public routines
            $routines = Routine::with(['user', 'exercises'])
                ->where('is_public', true)
                ->latest()
                ->get();
        }

        return Inertia::render('Routines/Routineview', [
            'routines' => $routines
        ]);
    }

    public function publicIndex()
    {
        $routines = Routine::where('is_public', true)
            ->with(['user', 'exercises'])
            ->latest()
            ->get();
            
        return Inertia::render('Routines/Public', [
            'routines' => $routines
        ]);
    }
    
    // Add this method to show user's own routines only
    public function myRoutines()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $routines = Routine::with(['user', 'exercises'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
            
        return Inertia::render('Routines/MyRoutines', [
            'routines' => $routines
        ]);
    }

    public function getRoutine(Routine $routine)
    {
        $user = auth()->user();
        
        // Pārbauda piekļuvi
        if ($routine->user_id !== $user->id && !$routine->is_public) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $routine->load(['exercises' => function($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes']);
        }]);
        
        return response()->json($routine);
    }

    /**
     * Set a routine as active for the current user
     */
    public function setActive(Routine $routine)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Nav autentificēts'], 401);
        }
        
        // Check access
        if ($routine->user_id !== $user->id && !$routine->is_public) {
            return response()->json([
                'success' => false,
                'error' => 'Nav pieejas šai rutīnai'
            ], 403);
        }
        
        // Save active routine in database (not session)
        $user->active_routine_id = $routine->id;
        $user->save();
        
        Log::info('RoutineController: Setting active routine - ID: ' . $routine->id . ', User: ' . $user->id);
        
        // Load routine with all necessary data for frontend
        $routine->load(['exercises' => function($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes'])
                  ->orderBy('exercise_routine.day_number');
        }]);
        
        // Format the response to match frontend expectations
        $formattedRoutine = [
            'id' => $routine->id,
            'name' => $routine->name,
            'description' => $routine->description,
            'is_public' => $routine->is_public,
            'user_id' => $routine->user_id,
            'exercises_count' => $routine->exercises->count(),
            'exercises' => $routine->exercises->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'muscle_group' => $exercise->muscle_group,
                    'description' => $exercise->description,
                    'day_number' => $exercise->pivot->day_number,
                    'sets' => $exercise->pivot->sets,
                    'reps' => $exercise->pivot->reps,
                    'rest_seconds' => $exercise->pivot->rest_seconds,
                    'notes' => $exercise->pivot->notes,
                    'pivot' => [
                        'day_number' => $exercise->pivot->day_number,
                        'sets' => $exercise->pivot->sets,
                        'reps' => $exercise->pivot->reps,
                        'rest_seconds' => $exercise->pivot->rest_seconds,
                        'notes' => $exercise->pivot->notes
                    ]
                ];
            })->toArray()
        ];
        
        return response()->json([
            'success' => true,
            'message' => 'Rutīna iestatīta kā aktīvā',
            'routine' => $formattedRoutine
        ]);
    }

    /**
     * Clear the active routine for the current user
     */
    public function clearActive()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        // Clear active routine from database (not session)
        $user->active_routine_id = null;
        $user->save();
        
        Log::info('RoutineController: Clearing active routine for User: ' . $user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Aktīvā rutīna notīrīta'
        ]);
    }

    /**
     * Get the current user's active routine data
     */
    public function getActiveRoutine()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        $activeRoutine = $user->activeRoutine;
        
        if (!$activeRoutine) {
            return response()->json([
                'success' => true,
                'active_routine' => null
            ]);
        }
        
        // Load routine with exercises
        $activeRoutine->load(['exercises' => function($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes'])
                  ->orderBy('exercise_routine.day_number');
        }]);
        
        $formattedRoutine = [
            'id' => $activeRoutine->id,
            'name' => $activeRoutine->name,
            'description' => $activeRoutine->description,
            'is_public' => $activeRoutine->is_public,
            'user_id' => $activeRoutine->user_id,
            'exercises_count' => $activeRoutine->exercises->count(),
            'exercises' => $activeRoutine->exercises->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'muscle_group' => $exercise->muscle_group,
                    'day_number' => $exercise->pivot->day_number,
                    'sets' => $exercise->pivot->sets,
                    'reps' => $exercise->pivot->reps,
                    'rest_seconds' => $exercise->pivot->rest_seconds,
                    'notes' => $exercise->pivot->notes,
                    'pivot' => [
                        'day_number' => $exercise->pivot->day_number,
                        'sets' => $exercise->pivot->sets,
                        'reps' => $exercise->pivot->reps,
                        'rest_seconds' => $exercise->pivot->rest_seconds,
                        'notes' => $exercise->pivot->notes
                    ]
                ];
            })->toArray()
        ];
        
        return response()->json([
            'success' => true,
            'active_routine' => $formattedRoutine
        ]);
    }

    public function edit(Routine $routine)
    {
        // Pārbaudam autorizāciju
        $user = auth()->user();
        if ($routine->user_id !== $user->id && !$routine->is_public) {
            abort(403, 'Unauthorized');
        }
        
        $routine->load(['exercises' => function($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'rest_seconds', 'notes']);
        }]);
        
        return inertia('Routines/Edit', [
            'routine' => $routine,
            'exercises' => Exercise::all(),
            'weekDays' => [
                1 => ['id' => 1, 'name' => 'Pirmdiena'],
                2 => ['id' => 2, 'name' => 'Otrdiena'],
                3 => ['id' => 3, 'name' => 'Trešdiena'],
                4 => ['id' => 4, 'name' => 'Ceturtdiena'],
                5 => ['id' => 5, 'name' => 'Piektdiena'],
                6 => ['id' => 6, 'name' => 'Sestdiena'],
                7 => ['id' => 7, 'name' => 'Svētdiena']
            ]
        ]);
    }

    public function update(Request $request, Routine $routine)
    {
        // Pārbaudam autorizāciju
        $user = auth()->user();
        if ($routine->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'exercises' => 'nullable|array',
            'exercises.*.id' => 'required|exists:exercises,id',
            'exercises.*.day_number' => 'required|integer|min:1|max:7',
            'exercises.*.sets' => 'required|integer|min:1',
            'exercises.*.reps' => 'required|integer|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.notes' => 'nullable|string',
        ]);
        
        // Atjauninām rutīnas pamatinformāciju
        $routine->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_public' => $validated['is_public'] ?? false,
        ]);
        
        // Ja ir vingrinājumi, atjauninām tos
        if (isset($validated['exercises'])) {
            $routine->exercises()->detach();
            
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
        }
        
        return redirect()->route('routines.my')->with('success', 'Rutīna veiksmīgi atjaunināta!');
    }
}
