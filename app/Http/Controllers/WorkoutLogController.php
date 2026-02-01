<?php

namespace App\Http\Controllers;

use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use App\Models\WorkoutLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class WorkoutLogController extends Controller
{
   public function index(Request $request)
{
    $user = Auth::user();
    
    // Izmantojam WorkoutLog modeli, nevis WorkoutSession
    $query = WorkoutLog::with(['routine', 'logExercises'])
        ->where('user_id', $user->id)
        ->orderBy('completed_at', 'desc');
    
    // Filtrēšana pēc meklēšanas
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('notes', 'like', "%{$search}%")
              ->orWhereHas('routine', function($q2) use ($search) {
                  $q2->where('name', 'like', "%{$search}%");
              });
        });
    }
    
    // Filtrēšana pēc mēneša
    if ($request->has('month') && $request->month) {
        $month = Carbon::parse($request->month . '-01');
        $query->whereBetween('completed_at', [
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth()
        ]);
    }
    
    $workoutLogs = $query->paginate(15)->withQueryString();
    
    // Pārveidojam datumus uz ISO string
    $workoutLogs->transform(function ($log) {
        return [
            'id' => $log->id,
            'name' => $log->name,
            'completed_at' => $log->completed_at?->toISOString(),
            'duration_minutes' => $log->duration_minutes,
            'calories_burned' => $log->calories_burned,
            'notes' => $log->notes,
            'routine' => $log->routine ? [
                'id' => $log->routine->id,
                'name' => $log->routine->name
            ] : null,
            // Izmantojam modela computed properties
            'total_sets' => $log->total_sets,
            'total_reps' => $log->total_reps,
            'total_weight' => $log->total_weight,
        ];
    });
    
    // Globālā statistika no WorkoutLog
    $stats = [
        'total_workouts' => WorkoutLog::where('user_id', $user->id)->count(),
        'total_duration' => WorkoutLog::where('user_id', $user->id)->sum('duration_minutes'),
        'total_calories' => WorkoutLog::where('user_id', $user->id)->sum('calories_burned'),
        'this_month_workouts' => WorkoutLog::where('user_id', $user->id)
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count(),
    ];
    
    // Mēnešu saraksts no WorkoutLog
    $months = WorkoutLog::where('user_id', $user->id)
        ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month')
        ->whereNotNull('completed_at')
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->get()
        ->map(function($item) {
            $date = Carbon::parse($item->month . '-01');
            return [
                'value' => $item->month,
                'label' => $date->translatedFormat('F Y')
            ];
        });
    
    return Inertia::render('WorkoutLogs/Index', [
        'workoutLogs' => $workoutLogs,
        'filters' => [
            'search' => $request->search,
            'month' => $request->month
        ],
        'stats' => $stats,
        'months' => $months
    ]);
}

// Palīgfunkcija treniņa statistikas aprēķināšanai
private function calculateSessionStats($session)
{
    $session->total_sets = 0;
    $session->total_reps = 0;
    $session->total_weight = 0;
    
    foreach ($session->exercises as $exercise) {
        // Sets
        $session->total_sets += $exercise->sets_completed;
        
        // Reps
        $reps = $exercise->reps_completed ?? [];
        if (is_array($reps)) {
            foreach ($reps as $repCount) {
                if (is_numeric($repCount)) {
                    $session->total_reps += $repCount;
                }
            }
        }
        
        // Weights
        $weights = $exercise->weights_used ?? [];
        if (is_array($weights)) {
            foreach ($weights as $weightData) {
                if (is_array($weightData) && isset($weightData['weight'])) {
                    $session->total_weight += floatval($weightData['weight']);
                } elseif (is_numeric($weightData)) {
                    $session->total_weight += floatval($weightData);
                }
            }
        }
    }
    
    $session->total_weight = round($session->total_weight, 2);
    
    // Ja nav duration_minutes, aprēķinam no starta un beigu laika
    if (!$session->duration_minutes && $session->ended_at && $session->started_at) {
        $session->duration_minutes = $session->started_at->diffInMinutes($session->ended_at);
    }
}

// Aprēķina globālo statistiku - VISI treniņi
private function calculateGlobalStats($userId)
{
    // 1. Kopējais treniņu skaits (VISI)
    $totalWorkouts = WorkoutSession::where('user_id', $userId)->count();
    
    // 2. Kopējais laiks (izmantojam duration_minutes vai aprēķinam no laika)
    $sessions = WorkoutSession::where('user_id', $userId)->get();
    $totalDuration = 0;
    $totalCalories = 0;
    
    foreach ($sessions as $session) {
        if ($session->duration_minutes) {
            $totalDuration += $session->duration_minutes;
        } elseif ($session->ended_at && $session->started_at) {
            $totalDuration += $session->started_at->diffInMinutes($session->ended_at);
        }
        
        $totalCalories += $session->calories_burned ?? 0;
    }
    
    // 3. Šī mēneša treniņi
    $thisMonthWorkouts = WorkoutSession::where('user_id', $userId)
        ->whereBetween('started_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->count();
    
    // 4. Kopējais setu, repu un svara daudzums
    $exerciseStats = DB::table('workout_session_exercises as wse')
        ->join('workout_sessions as ws', 'wse.workout_session_id', '=', 'ws.id')
        ->where('ws.user_id', $userId)
        ->selectRaw('
            SUM(wse.sets_completed) as total_sets,
            COUNT(DISTINCT ws.id) as workouts_with_exercises
        ')
        ->first();
    
    return [
        'total_workouts' => $totalWorkouts,
        'total_duration' => $totalDuration,
        'total_calories' => $totalCalories,
        'this_month_workouts' => $thisMonthWorkouts,
        'total_sets' => $exerciseStats->total_sets ?? 0,
        'workouts_with_exercises' => $exerciseStats->workouts_with_exercises ?? 0,
    ];
}

// Iegūst pieejamos mēnešus (izmantojam started_at)
private function getAvailableMonths($userId)
{
    $months = WorkoutSession::where('user_id', $userId)
        ->selectRaw('DATE_FORMAT(started_at, "%Y-%m") as month')
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->get()
        ->map(function($item) {
            $date = Carbon::parse($item->month . '-01');
            return [
                'value' => $item->month,
                'label' => $date->translatedFormat('F Y')
            ];
        });
    
    return $months;
}
}
