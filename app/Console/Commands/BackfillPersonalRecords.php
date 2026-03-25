<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WorkoutLogExercise;
use App\Models\PersonalRecord;
use Illuminate\Console\Command;

class BackfillPersonalRecords extends Command
{
    protected $signature = 'personal-records:backfill {user? : The ID of the user to backfill}';
    protected $description = 'Backfill personal records from existing workout logs';

    public function handle()
    {
        $userId = $this->argument('user');
        
        $users = $userId ? User::where('id', $userId)->get() : User::all();
        
        foreach ($users as $user) {
            $this->info("Processing user: {$user->name} (ID: {$user->id})");
            
            // Get all workout log exercises for this user
            $workoutLogExercises = WorkoutLogExercise::whereHas('workoutLog', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with('workoutLog')->get();
            
            // Group by exercise
            $groupedExercises = $workoutLogExercises->groupBy('exercise_id');
            
            $recordsCreated = 0;
            
            foreach ($groupedExercises as $exerciseId => $exercises) {
                $bestWeight = 0;
                $bestReps = 0;
                $bestSets = 0;
                $bestDate = null;
                $bestLogId = null;
                $notes = '';
                
                foreach ($exercises as $exercise) {
                    $weights = $exercise->weights_used ?? [];
                    $repsArray = $exercise->reps_completed ?? [];
                    
                    foreach ($weights as $index => $weightData) {
                        $weight = is_array($weightData) ? ($weightData['weight'] ?? 0) : $weightData;
                        $reps = $repsArray[$index] ?? 0;
                        
                        if ($weight > $bestWeight || 
                            ($weight == $bestWeight && $reps > $bestReps)) {
                            $bestWeight = $weight;
                            $bestReps = $reps;
                            $bestSets = $exercise->sets_completed;
                            $bestDate = $exercise->workoutLog->completed_at;
                            $bestLogId = $exercise->workout_log_id;
                            $notes = $exercise->notes;
                        }
                    }
                }
                
                if ($bestWeight > 0) {
                    // Create or update personal record
                    PersonalRecord::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'exercise_id' => $exerciseId,
                        ],
                        [
                            'weight' => $bestWeight,
                            'reps' => $bestReps,
                            'sets' => $bestSets,
                            'workout_log_id' => $bestLogId,
                            'achieved_at' => $bestDate,
                            'notes' => $notes,
                        ]
                    );
                    
                    $recordsCreated++;
                }
            }
            
            $this->info("Created/updated {$recordsCreated} personal records for {$user->name}");
        }
        
        $this->info('Personal records backfill completed!');
        
        return Command::SUCCESS;
    }
}
