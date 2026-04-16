<?php

namespace App\Http\Controllers;

use App\Models\WorkoutLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

class WorkoutLogController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = WorkoutLog::with(['routine', 'logExercises'])
            ->where('user_id', $user->id)
            ->orderBy('completed_at', 'desc');

        // Meklēšana pēc nosaukuma
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('routine', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // Filtrēšana pēc mēneša
        if ($request->filled('month')) {
            $query->where('completed_at', 'like', $request->month . '%');
        }

        $workoutLogs = $query->paginate(15)->withQueryString();

        $workoutLogs->transform(fn($log) => [
            'id'               => $log->id,
            'name'             => $log->name,
            'completed_at'     => $log->completed_at?->toISOString(),
            'duration_minutes' => $log->duration_minutes,
            'notes'            => $log->notes,
            'routine'          => $log->routine ? [
                'id'   => $log->routine->id,
                'name' => $log->routine->name,
            ] : null,
            'total_sets'   => $log->total_sets,
            'total_reps'   => $log->total_reps,
            'total_weight' => $log->total_weight,
        ]);

        $stats = [
            'total_workouts'       => WorkoutLog::where('user_id', $user->id)->count(),
            'total_duration'       => WorkoutLog::where('user_id', $user->id)->sum('duration_minutes'),
            'this_month_workouts'  => WorkoutLog::where('user_id', $user->id)
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
        ];

        $latvianMonths = [
            'January' => 'janvāris', 'February' => 'februāris', 'March' => 'marts',
            'April' => 'aprīlis',    'May' => 'maijs',           'June' => 'jūnijs',
            'July' => 'jūlijs',      'August' => 'augusts',      'September' => 'septembris',
            'October' => 'oktobris', 'November' => 'novembris',  'December' => 'decembris',
        ];

        $months = WorkoutLog::where('user_id', $user->id)
            ->selectRaw('DISTINCT DATE_FORMAT(completed_at, "%Y-%m") as month')
            ->whereNotNull('completed_at')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) use ($latvianMonths) {
                $date = Carbon::parse($item->month . '-01');
                return [
                    'value' => $item->month,
                    'label' => ($latvianMonths[$date->format('F')] ?? $date->format('F')) . ' ' . $date->format('Y'),
                ];
            });

        return Inertia::render('WorkoutLogs/Index', [
            'workoutLogs' => $workoutLogs,
            'filters'     => ['search' => $request->search, 'month' => $request->month],
            'stats'       => $stats,
            'months'      => $months,
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

        $workoutLog = WorkoutLog::with(['routine', 'logExercises.exercise'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $workoutLog->log_exercises = $workoutLog->logExercises->map(function ($logExercise) {
            $reps    = is_array($logExercise->reps_completed) ? $logExercise->reps_completed : [];
            $weights = is_array($logExercise->weights_used)   ? $logExercise->weights_used   : [];

            $setsData = [];
            for ($i = 0; $i < min(count($reps), $logExercise->sets_completed); $i++) {
                $setsData[] = [
                    'reps'   => $reps[$i] ?? 0,
                    'weight' => is_array($weights[$i]) ? ($weights[$i]['weight'] ?? 0) : ($weights[$i] ?? 0),
                ];
            }

            return [
                'id'             => $logExercise->id,
                'exercise'       => $logExercise->exercise ? [
                    'id'           => $logExercise->exercise->id,
                    'name'         => $logExercise->exercise->name,
                    'muscle_group' => $logExercise->exercise->muscle_group,
                ] : null,
                'sets_completed' => $logExercise->sets_completed,
                'sets_planned'   => $logExercise->sets_planned,
                'reps_planned'   => $logExercise->reps_planned,
                'reps_completed' => $reps,
                'weights_used'   => $weights,
                'sets_data'      => $setsData,
                'notes'          => $logExercise->notes,
            ];
        });

        $stats = $this->calculateWorkoutStats($workoutLog);

        $similarWorkouts = WorkoutLog::where('user_id', $user->id)
            ->where('id', '!=', $id)
            ->whereDate('completed_at', '>=', Carbon::now()->subMonth())
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'id'               => $log->id,
                'name'             => $log->name,
                'completed_at'     => $log->completed_at?->toISOString(),
                'duration_minutes' => $log->duration_minutes,
            ]);

        return Inertia::render('WorkoutLogs/Show', [
            'workoutLog' => [
                'id'               => $workoutLog->id,
                'name'             => $workoutLog->name,
                'completed_at'     => $workoutLog->completed_at?->toISOString(),
                'duration_minutes' => $workoutLog->duration_minutes,
                'notes'            => $workoutLog->notes,
                'routine'          => $workoutLog->routine ? [
                    'id'   => $workoutLog->routine->id,
                    'name' => $workoutLog->routine->name,
                ] : null,
                'log_exercises' => $workoutLog->log_exercises,
            ],
            'stats'          => $stats,
            'similarWorkouts' => $similarWorkouts,
        ]);
    }

    public function destroy($id)
    {
        $workoutLog = WorkoutLog::where('user_id', Auth::id())->findOrFail($id);
        $workoutLog->delete();
        return redirect()->route('workout-logs.index');
    }

    private function calculateWorkoutStats($workoutLog): array
    {
        $totalSets   = 0;
        $totalReps   = 0;
        $totalWeight = 0;
        $muscleGroups = [];

        foreach ($workoutLog->log_exercises as $ex) {
            $totalSets += $ex['sets_completed'];

            foreach ($ex['reps_completed'] as $reps) {
                if (is_numeric($reps)) $totalReps += $reps;
            }

            foreach ($ex['weights_used'] as $weight) {
                if (is_numeric($weight)) $totalWeight += $weight;
                elseif (is_array($weight) && isset($weight['weight'])) $totalWeight += $weight['weight'];
            }

            if ($ex['exercise'] && $ex['exercise']['muscle_group']) {
                $mg = $ex['exercise']['muscle_group'];
                $muscleGroups[$mg] = ($muscleGroups[$mg] ?? 0) + $ex['sets_completed'];
            }
        }

        return [
            'total_sets'              => $totalSets,
            'total_reps'              => $totalReps,
            'total_weight'            => round($totalWeight, 1),
            'average_reps_per_set'    => $totalSets > 0 ? round($totalReps / $totalSets, 1) : 0,
            'average_weight_per_set'  => $totalSets > 0 ? round($totalWeight / $totalSets, 1) : 0,
            'muscle_groups'           => $muscleGroups,
        ];
    }
}
