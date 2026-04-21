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

        // meklēšana pēc nosaukuma, piezīmēm vai rutīnas
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('routine', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        // filtrē pēc izvēlētā mēneša
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

        // vispārīgā statistika augšpanelim
        $stats = [
            'total_workouts'      => WorkoutLog::where('user_id', $user->id)->count(),
            'total_duration'      => WorkoutLog::where('user_id', $user->id)->sum('duration_minutes'),
            'this_month_workouts' => WorkoutLog::where('user_id', $user->id)
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
        ];

        // latvieši grib latviski mēnešus
        $latvianMonths = [
            'January' => 'janvāris',   'February' => 'februāris', 'March'    => 'marts',
            'April'   => 'aprīlis',    'May'      => 'maijs',      'June'     => 'jūnijs',
            'July'    => 'jūlijs',     'August'   => 'augusts',    'September' => 'septembris',
            'October' => 'oktobris',   'November' => 'novembris',  'December' => 'decembris',
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

            $reps      = is_array($logExercise->reps_completed)      ? $logExercise->reps_completed      : [];
            $weights   = is_array($logExercise->weights_used)        ? $logExercise->weights_used        : [];
            $durations = is_array($logExercise->durations_completed) ? $logExercise->durations_completed : [];

            $exerciseType   = $logExercise->exercise->type         ?? 'strength';
            $exerciseMuscle = $logExercise->exercise->muscle_group ?? '';
            $isCardio       = $exerciseType === 'cardio' || $exerciseMuscle === 'Kardio';

            return [
                'id'       => $logExercise->id,
                'exercise' => $logExercise->exercise ? [
                    'id'           => $logExercise->exercise->id,
                    'name'         => $logExercise->exercise->name,
                    'muscle_group' => $logExercise->exercise->muscle_group,
                    'type'         => $exerciseType,
                ] : null,
                'sets_completed'      => $logExercise->sets_completed,
                'sets_planned'        => $logExercise->sets_planned  ?? 0,
                'reps_planned'        => $logExercise->reps_planned  ?? 0,
                'reps_completed'      => $reps,
                'weights_used'        => $weights,
                'durations_completed' => $durations,
                // nodod frontendā lai zina vai rādīt kardio vai strength
                'is_cardio'           => $isCardio,
                'notes'               => $logExercise->notes,
            ];
        });

        $stats = $this->calculateWorkoutStats($workoutLog);

        // pēdējā mēneša treniņi sānjoslai
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
            'stats'           => $stats,
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
        $totalSets       = 0;
        $totalReps       = 0;
        $totalWeight     = 0;
        $totalCardioSets = 0;
        $totalCardioTime = 0; // sekundēs
        $muscleGroups    = [];

        foreach ($workoutLog->log_exercises as $ex) {
            $totalSets += $ex['sets_completed'];

            if ($ex['is_cardio']) {
                // kardio — skaita laiku, nevis svaru
                $totalCardioSets += $ex['sets_completed'];
                foreach ($ex['durations_completed'] as $dur) {
                    if (is_numeric($dur)) $totalCardioTime += $dur;
                }
            } else {
                // strength — parastais aprēķins
                foreach ($ex['reps_completed'] as $reps) {
                    if (is_numeric($reps)) $totalReps += $reps;
                }
                foreach ($ex['weights_used'] as $weight) {
                    if (is_numeric($weight)) $totalWeight += $weight;
                    elseif (is_array($weight) && isset($weight['weight'])) $totalWeight += $weight['weight'];
                }
            }

            if ($ex['exercise'] && $ex['exercise']['muscle_group']) {
                $mg = $ex['exercise']['muscle_group'];
                $muscleGroups[$mg] = ($muscleGroups[$mg] ?? 0) + $ex['sets_completed'];
            }
        }

        $strengthSets = $totalSets - $totalCardioSets;

        $cardioMins      = floor($totalCardioTime / 60);
        $cardioSecs      = $totalCardioTime % 60;
        $cardioFormatted = $totalCardioTime > 0
            ? ($cardioMins > 0 ? "{$cardioMins}min {$cardioSecs}s" : "{$cardioSecs}s")
            : null;

        return [
            'total_sets'             => $totalSets,
            'total_reps'             => $totalReps,
            'total_weight'           => round($totalWeight, 1),
            'average_reps_per_set'   => $strengthSets > 0 ? round($totalReps / $strengthSets, 1) : 0,
            'average_weight_per_set' => $strengthSets > 0 ? round($totalWeight / $strengthSets, 1) : 0,
            'total_cardio_seconds'   => $totalCardioTime,
            'total_cardio_formatted' => $cardioFormatted,
            'total_cardio_sets'      => $totalCardioSets,
            'muscle_groups'          => $muscleGroups,
        ];
    }
}
