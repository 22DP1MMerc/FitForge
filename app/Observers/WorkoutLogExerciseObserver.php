<?php

namespace App\Observers;

use App\Models\WorkoutLogExercise;
use App\Models\PersonalRecord;
use Illuminate\Support\Facades\Log;

class WorkoutLogExerciseObserver
{
    /**
     * Handle the WorkoutLogExercise "created" event.
     */
    public function created(WorkoutLogExercise $workoutLogExercise): void
    {
        $this->checkAndUpdatePersonalRecord($workoutLogExercise);
    }

    /**
     * Handle the WorkoutLogExercise "updated" event.
     */
    public function updated(WorkoutLogExercise $workoutLogExercise): void
    {
        $this->checkAndUpdatePersonalRecord($workoutLogExercise);
    }

    /**
     * Check if this workout contains a new personal record and update if needed
     */
    private function checkAndUpdatePersonalRecord(WorkoutLogExercise $workoutLogExercise): void
    {
        try {
            $userId = $workoutLogExercise->workoutLog->user_id;
            $exerciseId = $workoutLogExercise->exercise_id;
            
            $weights = $workoutLogExercise->weights_used ?? [];
            $repsArray = $workoutLogExercise->reps_completed ?? [];
            
            // Find the best set in this workout
            $bestWeight = 0;
            $bestReps = 0;
            $bestSetIndex = 0;
            
            foreach ($weights as $index => $weightData) {
                $weight = is_array($weightData) ? ($weightData['weight'] ?? 0) : $weightData;
                $reps = $repsArray[$index] ?? 0;
                
                if ($weight > $bestWeight) {
                    $bestWeight = $weight;
                    $bestReps = $reps;
                    $bestSetIndex = $index;
                } elseif ($weight == $bestWeight && $reps > $bestReps) {
                    $bestReps = $reps;
                    $bestSetIndex = $index;
                }
            }
            
            if ($bestWeight <= 0) {
                return; // No valid weight data
            }
            
            // Check if this is a new personal record
            if (PersonalRecord::isNewRecord($userId, $exerciseId, $bestWeight, $bestReps)) {
                // Create or update personal record
                PersonalRecord::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'exercise_id' => $exerciseId,
                    ],
                    [
                        'weight' => $bestWeight,
                        'reps' => $bestReps,
                        'sets' => $workoutLogExercise->sets_completed,
                        'workout_log_id' => $workoutLogExercise->workout_log_id,
                        'achieved_at' => $workoutLogExercise->workoutLog->completed_at,
                        'notes' => $workoutLogExercise->notes . 
                                  (isset($weights[$bestSetIndex]['notes']) ? 
                                   ' | Best set notes: ' . $weights[$bestSetIndex]['notes'] : ''),
                    ]
                );
                
                // You could also dispatch an event here for notifications
                // event(new NewPersonalRecordAchieved($personalRecord));
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update personal record: ' . $e->getMessage(), [
                'workout_log_exercise_id' => $workoutLogExercise->id,
            ]);
        }
    }
}
