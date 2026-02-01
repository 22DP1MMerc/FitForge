<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkoutSession;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogExercise;

class MigrateWorkoutSessionsToLogs extends Command
{
    protected $signature = 'workouts:migrate-to-logs';
    protected $description = 'Migrate completed workout sessions to workout logs';

    public function handle()
    {
        $this->info('Migrating workout sessions to logs...');
        
        // Migrējam tikai pabeigtos treniņus
        $sessions = WorkoutSession::with(['exercises'])
            ->whereIn('status', ['completed', 'cancelled'])
            ->get();
        
        $this->info("Found {$sessions->count()} sessions to migrate");
        
        $migratedCount = 0;
        
        foreach ($sessions as $session) {
            // Pārbaudām, vai jau eksistē logs šai sesijai
            $existingLog = WorkoutLog::where('workout_session_id', $session->id)->first();
            
            if ($existingLog) {
                $this->line("Session {$session->id} already has log #{$existingLog->id}");
                continue;
            }
            
            // Izveidojam logu
            $workoutLog = WorkoutLog::create([
                'user_id' => $session->user_id,
                'workout_session_id' => $session->id,
                'routine_id' => $session->routine_id,
                'name' => $session->name,
                'duration_minutes' => $session->duration_minutes ?? 
                    ($session->ended_at && $session->started_at 
                        ? $session->started_at->diffInMinutes($session->ended_at) 
                        : 0),
                'calories_burned' => $session->calories_burned ?? 0,
                'notes' => $session->notes ?? '',
                'completed_at' => $session->ended_at ?? $session->started_at,
            ]);
            
            // Migrējam vingrinājumus
            foreach ($session->exercises as $exercise) {
                WorkoutLogExercise::create([
                    'workout_log_id' => $workoutLog->id,
                    'exercise_id' => $exercise->exercise_id,
                    'sets_completed' => $exercise->sets_completed,
                    'reps_completed' => $exercise->reps_completed ?? [],
                    'weights_used' => $exercise->weights_used ?? [],
                    'notes' => $exercise->notes ?? '',
                ]);
            }
            
            $migratedCount++;
            $this->line("Migrated session #{$session->id} to log #{$workoutLog->id}");
        }
        
        $this->info("Successfully migrated {$migratedCount} sessions to logs!");
        
        return 0;
    }
}
