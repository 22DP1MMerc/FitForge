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
        // Nodod exercise type arī — vajag kardio noteikšanai
        $exercises = Exercise::all()->map(fn($e) => [
            'id'           => $e->id,
            'name'         => $e->name,
            'muscle_group' => $e->muscle_group,
            'description'  => $e->description,
            'equipment'    => $e->equipment,
            'type'         => $e->type ?? 'strength',
        ])->toArray();

        $activeWorkout = WorkoutSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        return Inertia::render('Workout/FreeWorkout', [
            'availableExercises' => $exercises,
            'initialWorkout' => [
                'name'      => 'Brīvais treniņš - ' . now()->format('d.m.Y'),
                'exercises' => [],
            ],
            'workoutSession' => $activeWorkout ? [
                'id'         => $activeWorkout->id,
                'name'       => $activeWorkout->name,
                'status'     => $activeWorkout->status,
                'started_at' => $activeWorkout->started_at->toISOString(),
            ] : null,
        ]);
    }

    // Sākt brīvo treniņu
    public function startFreeWorkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'routine_id'              => 'nullable|exists:routines,id',
            'exercises'               => 'nullable|array',
            'exercises.*.id'          => 'required|exists:exercises,id',
            'exercises.*.sets'        => 'required|integer|min:1',
            'exercises.*.reps'        => 'required|integer|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.notes'       => 'nullable|string',
        ]);

        // Atceļ iepriekšējos aktīvos treniņus
        $cancelled = WorkoutSession::where('user_id', Auth::id())
            ->where('status', 'active')
            ->update([
                'status'   => 'cancelled',
                'ended_at' => now(),
                'notes'    => 'Automātiski atcelts, jo sākts jauns treniņš',
            ]);

        $session = WorkoutSession::create([
            'user_id'    => Auth::id(),
            'routine_id' => $validated['routine_id'] ?? null,
            'name'       => $validated['name'],
            'status'     => 'active',
            'started_at' => now(),
        ]);

        foreach ($validated['exercises'] ?? [] as $exerciseData) {
            WorkoutSessionExercise::create([
                'workout_session_id' => $session->id,
                'exercise_id'        => $exerciseData['id'],
                'sets_planned'       => $exerciseData['sets'],
                'reps_planned'       => $exerciseData['reps'],
            ]);
        }

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
            'message'    => $cancelled > 0
                ? "Treniņš sākts. {$cancelled} iepriekšējie treniņi atcelti."
                : 'Treniņš sākts',
        ]);
    }

    // Aktīvā treniņa lapa
    public function active(WorkoutSession $workoutSession): Response|\Illuminate\Http\RedirectResponse
    {
        if ($workoutSession->user_id !== Auth::id()) abort(403);

        if ($workoutSession->status !== 'active') {
            return redirect()->route('dashboard')->with('error', 'Treniņš nav aktīvs');
        }

        $workoutSession->load(['exercises.exercise', 'routine']);

        return Inertia::render('Workout/Active', [
            'workoutSession' => [
                'id'         => $workoutSession->id,
                'name'       => $workoutSession->name,
                'type'       => $workoutSession->type,
                'status'     => $workoutSession->status,
                'started_at' => $workoutSession->started_at->toISOString(),
            ],
            'routine' => $workoutSession->routine ? [
                'id'          => $workoutSession->routine->id,
                'name'        => $workoutSession->routine->name,
                'description' => $workoutSession->routine->description,
            ] : null,
            'exercises' => $workoutSession->exercises->map(fn($ex) => [
                'id'                  => $ex->exercise_id,
                'session_exercise_id' => $ex->id,
                'name'                => $ex->exercise->name,
                'muscle_group'        => $ex->exercise->muscle_group,
                'type'                => $ex->exercise->type ?? 'strength',
                'sets'                => $ex->sets_planned,
                'reps'                => $ex->reps_planned,
                'rest_seconds'        => $ex->rest_time ?? 60,
                'sets_completed'      => $ex->sets_completed,
                'reps_completed'      => $ex->reps_completed      ?? [],
                'weights_used'        => $ex->weights_used        ?? [],
                'durations_completed' => $ex->durations_completed ?? [],
            ]),
        ]);
    }

    // Pabeigt treniņu — pārnes VISUS datus uz log
    public function completeWorkout(Request $request, WorkoutSession $workoutSession): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) abort(403);

        $request->validate([
            'duration_minutes' => 'required|integer|min:0',
            'notes'            => 'nullable|string',
        ]);

        $workoutSession->update([
            'status'           => 'completed',
            'ended_at'         => Carbon::now(),
            'duration_minutes' => $request->duration_minutes,
            'notes'            => $request->notes,
        ]);

        $workoutLog = WorkoutLog::create([
            'user_id'            => Auth::id(),
            'workout_session_id' => $workoutSession->id,
            'routine_id'         => $workoutSession->routine_id,
            'name'               => $workoutSession->name,
            'duration_minutes'   => $request->duration_minutes,
            'notes'              => $request->notes,
            'completed_at'       => $workoutSession->ended_at,
        ]);

        // Pārnes katru vingrinājumu — kardio saņem durations, strength reps+weight
        foreach ($workoutSession->exercises as $ex) {
            $isCardio = ($ex->exercise->type ?? 'strength') === 'cardio'
                     || ($ex->exercise->muscle_group ?? '') === 'Kardio';

            $workoutLog->logExercises()->create([
                'exercise_id'         => $ex->exercise_id,
                'sets_completed'      => $ex->sets_completed,
                'reps_completed'      => $isCardio ? [] : ($ex->reps_completed ?? []),
                'weights_used'        => $isCardio ? [] : ($ex->weights_used   ?? []),
                // Kardio laiki — tukšs ja nav kardio
                'durations_completed' => $isCardio ? ($ex->durations_completed ?? []) : [],
                'notes'               => $ex->notes ?? '',
            ]);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Treniņš veiksmīgi pabeigts!',
            'redirect_url' => '/dashboard',
        ]);
    }

    // Atcelt treniņu
    public function cancelWorkout(WorkoutSession $workoutSession): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) abort(403);

        $workoutSession->update([
            'status'   => 'cancelled',
            'ended_at' => Carbon::now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Treniņš atcelts']);
    }

    // Pievienot vingrinājumu sesijai
    public function addExercise(Request $request, WorkoutSession $workoutSession): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) abort(403);

        $request->validate([
            'exercise_id'  => 'required|exists:exercises,id',
            'sets_planned' => 'required|integer|min:1',
            'reps_planned' => 'required|integer|min:1',
        ]);

        $ex = $workoutSession->exercises()->create([
            'exercise_id'    => $request->exercise_id,
            'sets_planned'   => $request->sets_planned,
            'reps_planned'   => $request->reps_planned,
            'sets_completed' => 0,
        ]);

        return response()->json(['success' => true, 'session_exercise_id' => $ex->id]);
    }

    // Saglabāt setu — kardio saņem duration_seconds, strength reps+weight
    public function updateSet(Request $request, WorkoutSession $workoutSession, $exercise): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) abort(403);

        $request->validate([
            'set_index'        => 'required|integer|min:0',
            'reps'             => 'nullable|integer|min:0',
            'weight'           => 'nullable|numeric|min:0',
            // Kardio lauks — sekundes
            'duration_seconds' => 'nullable|integer|min:0',
        ]);

        $sessionExercise = $workoutSession->exercises()->findOrFail($exercise);

        $isCardio = ($sessionExercise->exercise->type ?? 'strength') === 'cardio'
                 || ($sessionExercise->exercise->muscle_group ?? '') === 'Kardio';

        $idx = $request->set_index;

        if ($isCardio) {
            // Kardio — saglabā laiku sekundēs
            $durations       = $sessionExercise->durations_completed ?? [];
            $durations[$idx] = $request->duration_seconds ?? 0;

            // Skaita pabeigtos kardio setus (laiks > 0)
            $setsCompleted = count(array_filter($durations, fn($d) => $d > 0));

            $sessionExercise->update([
                'durations_completed' => $durations,
                'sets_completed'      => $setsCompleted,
            ]);
        } else {
            // Strength — saglabā atkārtojumus un svaru
            $reps    = $sessionExercise->reps_completed ?? [];
            $weights = $sessionExercise->weights_used   ?? [];

            $reps[$idx]    = $request->reps   ?? 0;
            $weights[$idx] = $request->weight ?? 0;

            // Skaita pabeigtos strength setus (reps > 0)
            $setsCompleted = count(array_filter($reps, fn($r) => $r > 0));

            $sessionExercise->update([
                'reps_completed' => $reps,
                'weights_used'   => $weights,
                'sets_completed' => $setsCompleted,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // Noņemt vingrinājumu no sesijas
    public function removeExercise(WorkoutSession $workoutSession, $exercise): JsonResponse
    {
        if ($workoutSession->user_id !== Auth::id()) abort(403);

        $workoutSession->exercises()->findOrFail($exercise)->delete();

        return response()->json(['success' => true]);
    }

    // Sākt treniņu no rutīnas
    public function startFromRoutine(Routine $routine): \Illuminate\Http\RedirectResponse
    {
        if ($routine->user_id !== Auth::id()) abort(403);

        $session = WorkoutSession::create([
            'user_id'    => Auth::id(),
            'routine_id' => $routine->id,
            'name'       => $routine->name . ' - ' . Carbon::now()->format('d.m.Y'),
            'type'       => 'routine',
            'status'     => 'active',
            'started_at' => Carbon::now(),
        ]);

        foreach ($routine->exercises as $exercise) {
            $session->exercises()->create([
                'exercise_id'    => $exercise->id,
                'sets_planned'   => $exercise->pivot->sets ?? 3,
                'reps_planned'   => $exercise->pivot->reps ?? 10,
                'sets_completed' => 0,
            ]);
        }

        return redirect()->route('workout.active', $session);
    }
}
