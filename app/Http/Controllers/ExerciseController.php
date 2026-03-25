<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $query = Exercise::query();

        if ($request->has('muscle_group')) {
            $query->where('muscle_group', $request->muscle_group);
        }

        if ($request->has('equipment')) {
            $query->where('equipment', $request->equipment);
        }
        $exercises = $query->get();

         if (Auth::check()) {
        $exercises->load(['personalRecords' => function ($query) {
            $query->where('user_id', Auth::id())
                  ->orderBy('weight', 'desc')
                  ->orderBy('reps', 'desc')
                  ->orderBy('achieved_at', 'desc');
        }]);
    }

        
        $muscleGroups = Exercise::distinct()->pluck('muscle_group');
        $equipmentOptions = Exercise::distinct()->pluck('equipment');

        return inertia('Exercview', [
            'exercises' => $exercises,
            'muscleGroups' => $muscleGroups,
            'equipmentOptions' => $equipmentOptions,
            'filters' => $request->only(['muscle_group', 'equipment']),
        ]);
    }
}
