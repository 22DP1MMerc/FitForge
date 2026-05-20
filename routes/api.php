<?php

use App\Models\WorkoutSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => response()->json(['status' => 'ok']));

Route::middleware('auth')->group(function () {

    // Aktīvās sesijas dati — izmanto FreeWorkout.vue
    Route::get('/workout-session/{id}', function ($id) {
        $session = WorkoutSession::with(['exercises.exercise'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$session) {
            return response()->json(['error' => 'Sesija nav atrasta'], 404);
        }

        return response()->json([
            'id'               => $session->id,
            'name'             => $session->name,
            'status'           => $session->status,
            'started_at'       => $session->started_at,
            'duration_minutes' => $session->duration_minutes,
            'exercises'        => $session->exercises->map(fn($ex) => [
                'id'                   => $ex->exercise_id,
                'session_exercise_id'  => $ex->id,
                'name'                 => $ex->exercise->name,
                'muscle_group'         => $ex->exercise->muscle_group,
                'type'                 => $ex->exercise->type ?? 'strength',
                'sets_planned'         => $ex->sets_planned,
                'reps_planned'         => $ex->reps_planned,
                'sets_completed'       => $ex->sets_completed,
                'reps_completed'       => $ex->reps_completed       ?? [],
                'weights_used'         => $ex->weights_used         ?? [],
                'durations_completed'  => $ex->durations_completed  ?? [],
            ]),
        ]);
    });
});
