<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Routine;
use App\Models\WorkoutLog;
use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    // Brīvā treniņa lapa
    public function freeWorkout(): Response
    {
        $exercises = Exercise::all()->map(fn($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'muscle_group' => $e->muscle_group,
            'description' => $e->description,
            'equipment' => $e->equipment,
        ])->toArray();

        // Pārbauda vai jau ir aktīvs treniņš
        $activeWorkout = WorkoutSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        return Inertia::render('Workout/FreeWorkout', [
            'availableExercises' => $exercises,
            'initialWorkout' => [
                'name' => 'Brīvais treniņš - ' . now()->format('d.m.Y'),
                'exercises' => [],
            ],
            'workoutSession' => $activeWorkout ? [
                'id' => $activeWorkout->id,
                'name' => $activeWorkout->name,
                'status' => $activeWorkout->status,
                'started_at' => $activeWorkout->started_at->toISOString(),
            ] : null,
        ]);
    }

    // Sākt brīvo treniņu
    public function startFreeWorkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'routine_id' => 'nullable|exists:routines,id',
            'exercises' => 'nullable|array',
            'exercises.*.id' => 'required|exists:exercises,id',
            'exercises.*.sets' => 'required|integer|min:1',
            'exercises.*.reps' => 'required|integer|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.notes' => 'nullable|string',
        ]);

        // Atceļ visus iepriekšējos aktīvos treniņus
        $cancelled = WorkoutSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'ended_at' => now(),
                'notes' => 'Automātiski atcelts, jo sākts jauns treniņš',
            ]);

        $session = WorkoutSession::create([
            'user_id' => Auth::id(),
            'routine_id' => $validated['routine_id'] ?? null,
            'name' => $validated['name'],
            'status' => 'active',
            'started_at' => now(),
        ]);

        foreach ($validated['exercises'] ?? [] as $exerciseData) {
            WorkoutSessionExercise::create([
                'workout_session_id' => $session->id,
                'exercise_id' => $exerciseData['id'],
                'sets_planned' => $exerciseData['sets'],
                'reps_planned' => $exerciseData['reps'],
                'rest_seconds' => $exerciseData['rest_seconds'] ?? 60,
                'notes' => $exerciseData['notes'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'message' => $cancelled > 0
                ? "Treniņš sākts. {$cancelled} iepriekšējie treniņi atcelti."
                : 'Treniņš sākts',
        ]);
    }

    // Aktīvā treniņa lapa
    public function active(WorkoutSession $workoutSession): Response|\Illuminate\Http\RedirectResponse
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        if ($workoutSession->status !== 'active') {
            return redirect()->route('dashboard')->with('error', 'Treniņš nav aktīvs');
        }

        $workoutSession->load(['exercises.exercise', 'routine']);

        return Inertia::render('Workout/Active', [
            'workoutSession' => [
                'id' => $workoutSession->id,
                'name' => $workoutSession->name,
                'type' => $workoutSession->type,
                'status' => $workoutSession->status,
                'started_at' => $workoutSession->started_at->toISOString(),
            ],
            'routine' => $workoutSession->routine ? [
                'id' => $workoutSession->routine->id,
                'name' => $workoutSession->routine->name,
                'description' => $workoutSession->routine->description,
            ] : null,
            'exercises' => $workoutSession->exercises->map(fn($ex) => [
                'id' => $ex->exercise_id,
                'session_exercise_id' => $ex->id,
                'name' => $ex->exercise->name,
                'muscle_group' => $ex->exercise->muscle_group,
                'sets' => $ex->sets_planned,
                'reps' => $ex->reps_planned,
                'rest_seconds' => $ex->rest_time ?? 60,
                'sets_completed' => $ex->sets_completed,
                'reps_completed' => $ex->reps_completed ?? [],
                'weights_used' => $ex->weights_used ?? [],
            ]),
        ]);
    }

    // Pabeigt treniņu
    public function completeWorkout(Request $request, WorkoutSession $workoutSession): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'duration_minutes' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $workoutSession->update([
            'status' => 'completed',
            'ended_at' => Carbon::now(),
            'duration_minutes' => $request->duration_minutes,
            'notes' => $request->notes,
        ]);

        $workoutLog = WorkoutLog::create([
            'user_id' => Auth::id(),
            'workout_session_id' => $workoutSession->id,
            'routine_id' => $workoutSession->routine_id,
            'name' => $workoutSession->name,
            'duration_minutes' => $request->duration_minutes,
            'notes' => $request->notes,
            'completed_at' => $workoutSession->ended_at,
        ]);

        // Pārnes vingrinājumus uz žurnālu
        foreach ($workoutSession->exercises as $ex) {
            $workoutLog->logExercises()->create([
                'exercise_id' => $ex->exercise_id,
                'sets_completed' => $ex->sets_completed,
                'reps_completed' => $ex->reps_completed ?? [],
                'weights_used' => $ex->weights_used ?? [],
                'notes' => $ex->notes ?? '',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Treniņš veiksmīgi pabeigts!',
            'redirect_url' => '/dashboard',
        ]);
    }

    // Atcelt treniņu
    public function cancelWorkout(WorkoutSession $workoutSession): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        $workoutSession->update([
            'status' => 'cancelled',
            'ended_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Treniņš atcelts']);
    }

    // Pievienot vingrinājumu sesijai
    public function addExercise(Request $request, WorkoutSession $workoutSession): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'sets_planned' => 'required|integer|min:1',
            'reps_planned' => 'required|integer|min:1',
        ]);

        $workoutSession->exercises()->create([
            'exercise_id' => $request->exercise_id,
            'sets_planned' => $request->sets_planned,
            'reps_planned' => $request->reps_planned,
            'sets_completed' => 0,
        ]);

        return response()->json(['success' => true]);
    }

    // Atjaunināt setu
    public function updateSet(Request $request, WorkoutSession $workoutSession, $exercise): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'set_index' => 'required|integer|min:0',
            'reps' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $sessionExercise = $workoutSession->exercises()->findOrFail($exercise);

        $repsCompleted = $sessionExercise->reps_completed ?? [];
        $weightsUsed = $sessionExercise->weights_used ?? [];

        $repsCompleted[$request->set_index] = $request->reps;
        $weightsUsed[$request->set_index] = $request->weight;

        // Skaita cik setu ir pabeigti
        $setsCompleted = count(array_filter($repsCompleted, fn($r) => $r > 0));

        $sessionExercise->update([
            'reps_completed' => $repsCompleted,
            'weights_used' => $weightsUsed,
            'sets_completed' => $setsCompleted,
        ]);

        return response()->json(['success' => true]);
    }

    // Noņemt vingrinājumu no sesijas
    public function removeExercise(WorkoutSession $workoutSession, $exercise): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) {
            abort(403);
        }

        $workoutSession->exercises()->findOrFail($exercise)->delete();

        return response()->json(['success' => true]);
    }

    // Sākt treniņu no rutīnas
    public function startFromRoutine(Routine $routine): \Illuminate\Http\RedirectResponse
    {
        if ($routine->user_id !== Auth::id()) {
            abort(403);
        }

        $session = WorkoutSession::create([
            'user_id' => Auth::id(),
            'routine_id' => $routine->id,
            'name' => $routine->name . ' - ' . Carbon::now()->format('d.m.Y'),
            'type' => 'routine',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        foreach ($routine->exercises as $exercise) {
            $session->exercises()->create([
                'exercise_id' => $exercise->id,
                'sets_planned' => $exercise->pivot->sets ?? 3,
                'reps_planned' => $exercise->pivot->reps ?? 10,
                'sets_completed' => 0,
            ]);
        }

        return redirect()->route('workout.active', $session);
    }
}
