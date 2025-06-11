<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;

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
