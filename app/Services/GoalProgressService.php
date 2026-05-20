<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\PersonalRecord;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogExercise;
use App\Models\User;

class GoalProgressService
{
    /**
     * Recalculate and save progress for all auto-tracked goals of the given user.
     * Auto-marks a goal completed when current_value reaches target_value.
     */
    public function updateForUser(User $user): void
    {
        $goals = $user->goals()
            ->whereIn('type', ['workout', 'strength', 'endurance'])
            ->where('completed', false)
            ->get();

        foreach ($goals as $goal) {
            $new = match ($goal->type) {
                'workout'   => $this->calcWorkouts($user),
                'strength'  => $this->calcStrength($user, $goal),
                'endurance' => $this->calcEndurance($user),
                default     => null,
            };

            if ($new === null) continue;

            $goal->current_value = $new;
            if ($new >= $goal->target_value) {
                $goal->completed = true;
            }
            $goal->save();
        }
    }

    // Total completed workouts
    private function calcWorkouts(User $user): int
    {
        return WorkoutLog::where('user_id', $user->id)->count();
    }

    // Best personal record weight for a linked exercise
    private function calcStrength(User $user, Goal $goal): ?float
    {
        if (!$goal->exercise_id) return null;

        $pr = PersonalRecord::where('user_id', $user->id)
            ->where('exercise_id', $goal->exercise_id)
            ->orderByDesc('weight')
            ->first();

        return $pr ? (float) $pr->weight : null;
    }

    // Total cardio minutes across all workout logs
    private function calcEndurance(User $user): float
    {
        $exercises = WorkoutLogExercise::whereHas(
            'workoutLog',
            fn($q) => $q->where('user_id', $user->id)
        )->whereHas(
            'exercise',
            fn($q) => $q->where('type', 'cardio')->orWhere('muscle_group', 'Kardio')
        )->get();

        $totalSeconds = $exercises->sum(fn($ex) => array_sum($ex->durations_completed ?? []));

        return round($totalSeconds / 60, 1);
    }
}
