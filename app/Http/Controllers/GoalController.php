<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Goal;
use App\Services\GoalProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function __construct(private GoalProgressService $progress) {}

    public function index(): JsonResponse
    {
        $goals = Auth::user()
            ->goals()
            ->with('exercise:id,name,type,muscle_group')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($goals);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'type'         => ['required', Rule::in(['workout', 'strength', 'endurance'])],
            'exercise_id'  => [
                Rule::requiredIf($request->type === 'strength'),
                'nullable',
                'exists:exercises,id',
            ],
            'target_value' => 'required|numeric|min:0.01',
            'unit'         => 'nullable|string|max:50',
            'deadline'     => 'nullable|date',
        ]);

        $validated['user_id']       = Auth::id();
        $validated['current_value'] = 0;

        $goal = Goal::create($validated);

        // Compute initial progress immediately so the card shows real data from the start
        $this->progress->updateForUser(Auth::user());
        $goal->refresh()->load('exercise:id,name,type,muscle_group');

        return response()->json($goal, 201);
    }

    public function update(Request $request, Goal $goal): JsonResponse
    {
        if ($goal->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'type'         => ['sometimes', Rule::in(['workout', 'strength', 'endurance'])],
            'exercise_id'  => 'nullable|exists:exercises,id',
            'target_value' => 'sometimes|numeric|min:0.01',
            'current_value'=> 'sometimes|numeric|min:0',
            'unit'         => 'nullable|string|max:50',
            'deadline'     => 'nullable|date',
            'completed'    => 'sometimes|boolean',
        ]);

        $goal->update($validated);

        // Re-check completion threshold if target changed
        if (isset($validated['target_value']) && $goal->current_value >= $goal->target_value) {
            $goal->update(['completed' => true]);
        }

        return response()->json($goal->fresh()->load('exercise:id,name,type,muscle_group'));
    }

    public function destroy(Goal $goal): JsonResponse
    {
        if ($goal->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $goal->delete();

        return response()->json(['message' => 'Goal deleted successfully']);
    }

    /** Manually trigger recalculation for all auto-tracked goals */
    public function recalculate(): JsonResponse
    {
        $this->progress->updateForUser(Auth::user());

        $goals = Auth::user()
            ->goals()
            ->with('exercise:id,name,type,muscle_group')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($goals);
    }

    /** Strength exercises list for the goal form picker */
    public function strengthExercises(): JsonResponse
    {
        $exercises = Exercise::where('type', 'strength')
            ->select('id', 'name', 'muscle_group')
            ->orderBy('muscle_group')
            ->orderBy('name')
            ->get();

        return response()->json($exercises);
    }
}
