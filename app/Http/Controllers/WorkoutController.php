<?php

namespace App\Http\Controllers;

use App\Models\WorkoutSession;
use App\Models\Routine;
use App\Models\Exercise;
use App\Models\WorkoutLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class WorkoutController extends Controller
{
    // Treniņu dashboard (rāda aktīvo treniņu)
    public function dashboard()
    {
        $user = Auth::user();
        
        $activeWorkout = WorkoutSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        if ($activeWorkout) {
            return redirect()->route('workout.active', $activeWorkout);
        }
        
        return redirect()->route('dashboard');
    }
    
    // Sākt treniņu no rutīnas
    public function startFromRoutine(Routine $routine)
    {
        $user = Auth::user();
        
        // Pārbauda, vai rutīna pieder lietotājam
        if ($routine->user_id !== $user->id) {
            abort(403);
        }
        
        // Izveido jaunu treniņa sesiju no rutīnas
        $session = WorkoutSession::create([
            'user_id' => $user->id,
            'routine_id' => $routine->id,
            'name' => $routine->name . ' - ' . Carbon::now()->format('d.m.Y'),
            'type' => 'routine',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);
        
        // Pievieno vingrinājumus no rutīnas
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



    // Sākt brīvo treniņu
    public function startFreeWorkout(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'nullable|string|max:255|default:"Brīvais treniņš"',
        ]);
        
        // Izveido jaunu treniņa sesiju
        $session = WorkoutSession::create([
            'user_id' => $user->id,
            'name' => $request->name ?? 'Brīvais treniņš',
            'type' => 'free',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);
        
        return redirect()->route('workout.active', $session);
    }
    
    // Aktīvā treniņa lapa (GET maršruts)
    public function active(WorkoutSession $workoutSession)
    {
        $user = Auth::user();
        
        // Pārbauda, vai sesija pieder lietotājam
        if ($workoutSession->user_id !== $user->id) {
            abort(403);
        }
        
        // Pārbauda, vai sesija ir aktīva
        if ($workoutSession->status !== 'active') {
            return redirect()->route('dashboard')->with('error', 'Treniņš nav aktīvs');
        }
        
        $workoutSession->load(['exercises.exercise', 'routine']);
        
        return Inertia::render('Workout/Active', [
            'session' => [
                'id' => $workoutSession->id,
                'name' => $workoutSession->name,
                'type' => $workoutSession->type,
                'started_at' => $workoutSession->started_at,
                'routine_name' => $workoutSession->routine?->name,
            ],
            'exercises' => $workoutSession->exercises->map(function($sessionExercise) {
                return [
                    'id' => $sessionExercise->id,
                    'exercise_id' => $sessionExercise->exercise_id,
                    'name' => $sessionExercise->exercise->name,
                    'sets_planned' => $sessionExercise->sets_planned,
                    'reps_planned' => $sessionExercise->reps_planned,
                    'sets_completed' => $sessionExercise->sets_completed,
                    'reps_completed' => $sessionExercise->reps_completed ?? [],
                    'weights_used' => $sessionExercise->weights_used ?? [],
                    'notes' => $sessionExercise->notes ?? ''
                ];
            }),
            'availableExercises' => Exercise::all()->map(function($exercise) {
                return [
                    'id' => $exercise->id,
                    'name' => $exercise->name,
                    'muscle_group' => $exercise->muscle_group,
                ];
            })
        ]);
    }
    
    // Pievienot vingrinājumu
    public function addExercise(Request $request, WorkoutSession $workoutSession)
    {
        $user = Auth::user();
        
        if ($workoutSession->user_id !== $user->id) {
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
    public function updateSet(Request $request, WorkoutSession $workoutSession, $exercise)
    {
        $user = Auth::user();
        
        if ($workoutSession->user_id !== $user->id) {
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
        
        // Pievieno jaunu setu vai atjaunina esošo
        $repsCompleted[$request->set_index] = $request->reps;
        $weightsUsed[$request->set_index] = $request->weight;
        
        // Aprēķina pabeigto setu skaitu
        $setsCompleted = count(array_filter($repsCompleted, function($reps) {
            return $reps > 0;
        }));
        
        $sessionExercise->update([
            'reps_completed' => $repsCompleted,
            'weights_used' => $weightsUsed,
            'sets_completed' => $setsCompleted,
        ]);
        
        return response()->json(['success' => true]);
    }
    
    // Noņemt vingrinājumu
    public function removeExercise(WorkoutSession $workoutSession, $exercise)
    {
        $user = Auth::user();
        
        if ($workoutSession->user_id !== $user->id) {
            abort(403);
        }
        
        $sessionExercise = $workoutSession->exercises()->findOrFail($exercise);
        $sessionExercise->delete();
        
        return response()->json(['success' => true]);
    }
    
    // Pabeigt treniņu
    public function completeWorkout(Request $request, WorkoutSession $workoutSession)
    {
        $user = Auth::user();
        
        if ($workoutSession->user_id !== $user->id) {
            abort(403);
        }
        
        $request->validate([
            'duration_minutes' => 'required|integer|min:1',
            'calories_burned' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
        
        // Aprēķina kopējo ilgumu (ja nav norādīts)
        $durationMinutes = $request->duration_minutes;
        if (!$durationMinutes) {
            $durationMinutes = $workoutSession->started_at->diffInMinutes(Carbon::now());
        }
        
        // Atjaunina sesiju
        $workoutSession->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
            'duration_minutes' => $durationMinutes,
            'calories_burned' => $request->calories_burned,
            'notes' => $request->notes,
        ]);
        
        // Izveido treniņa logu
        WorkoutLog::create([
            'user_id' => $user->id,
            'routine_id' => $workoutSession->routine_id,
            'name' => $workoutSession->name,
            'duration_minutes' => $durationMinutes,
            'calories_burned' => $request->calories_burned,
            'notes' => $request->notes,
            'completed_at' => Carbon::now(),
        ]);
        
        return redirect()->route('dashboard')->with('success', 'Treniņš veiksmīgi pabeigts!');
    }
    
    // Atcelt treniņu
    public function cancelWorkout(WorkoutSession $workoutSession)
    {
        $user = Auth::user();
        
        if ($workoutSession->user_id !== $user->id) {
            abort(403);
        }
        
        $workoutSession->update([
            'status' => 'cancelled',
            'completed_at' => Carbon::now(),
        ]);
        
        return redirect()->route('dashboard')->with('info', 'Treniņš atcelts');
    }
    
    // Sākt treniņu (GET maršruts)
    public function start(Routine $routine)
    {
        return $this->startFromRoutine($routine);
    }
    
    // Pievieno arī citas metodes no tava maršrutu saraksta
    public function reorderExercise(Request $request, WorkoutSession $workoutSession)
    {
        // Implementē pēc vajadzības
        return response()->json(['success' => true]);
    }
    
    public function togglePause(WorkoutSession $workoutSession)
    {
        // Implementē pēc vajadzības
        return response()->json(['success' => true]);
    }
    
    public function editRoutineDuringWorkout(WorkoutSession $workoutSession)
    {
        // Implementē pēc vajadzības
        return Inertia::render('Workout/EditRoutine', [
            'session' => $workoutSession,
        ]);
    }
}
