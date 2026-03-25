<?php
// app/Http/Controllers/GoalController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $goals = Auth::user()
                ->goals()
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($goals);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load goals: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => ['required', Rule::in(['workout', 'weight', 'strength', 'endurance'])],
                'target_value' => 'required|numeric|min:0',
                'unit' => 'nullable|string|max:50',
                'deadline' => 'nullable|date'
            ]);

            // Add user_id and set current_value to 0
            $validated['user_id'] = Auth::id();
            $validated['current_value'] = 0;
            
            $goal = Goal::create($validated);
            
            return response()->json($goal, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create goal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Goal $goal): JsonResponse
    {
        try {
            if ($goal->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'type' => ['sometimes', Rule::in(['workout', 'weight', 'strength', 'endurance'])],
                'target_value' => 'sometimes|numeric|min:0',
                'current_value' => 'sometimes|numeric|min:0',
                'unit' => 'nullable|string|max:50',
                'deadline' => 'nullable|date',
                'completed' => 'sometimes|boolean'
            ]);

            $goal->update($validated);
            
            return response()->json($goal);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update goal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Goal $goal): JsonResponse
    {
        try {
            if ($goal->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $goal->delete();
            
            return response()->json(['message' => 'Goal deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete goal: ' . $e->getMessage()], 500);
        }
    }
}
