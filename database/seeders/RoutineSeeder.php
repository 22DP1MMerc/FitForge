<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoutineSeeder extends Seeder
{
    public function run()
    {
        // Get or create a test user
        $user = User::firstOrCreate([
            'email' => 'test@example.com'
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password')
        ]);

        // Get some exercises
        $exercises = Exercise::limit(10)->get();
        
        if ($exercises->isEmpty()) {
            // Create some exercises if none exist
            $exercises = Exercise::factory()->count(10)->create();
        }

        // Create sample routines
        $routine1 = Routine::create([
            'name' => 'Beginner Full Body Workout',
            'description' => 'A simple full-body routine for beginners',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $routine2 = Routine::create([
            'name' => 'Advanced Push/Pull/Legs',
            'description' => '6-day PPL split for advanced lifters',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $routine3 = Routine::create([
            'name' => 'My Personal Routine',
            'description' => 'Custom routine for my specific goals',
            'user_id' => $user->id,
            'is_public' => false
        ]);

         $routine4 = Routine::create([
            'name' => 'Hypertrophy Split',
            'description' => '5-day hypertrophy program focusing on muscle growth.',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $routine5 = Routine::create([
            'name' => 'Cardio & Core',
            'description' => 'Alternating cardio and core for fat loss and endurance.',
            'user_id' => $user->id,
            'is_public' => false
        ]);

        $routine6 = Routine::create([
            'name' => 'Push/Pull Split',
            'description' => 'Classic push/pull split for balanced upper body development.',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        // Attach exercises to routines with pivot data
        $routine1->exercises()->attach([
            $exercises[0]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 10, 'rest_seconds' => 60],
            $exercises[1]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 12, 'rest_seconds' => 45],
            $exercises[2]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 8, 'rest_seconds' => 90],
        ]);

        $routine2->exercises()->attach([
            $exercises[3]->id => ['day_number' => 1, 'sets' => 4, 'reps' => 6, 'rest_seconds' => 120],
            $exercises[4]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 8, 'rest_seconds' => 90],
            $exercises[5]->id => ['day_number' => 2, 'sets' => 5, 'reps' => 5, 'rest_seconds' => 180],
        ]);

        $routine3->exercises()->attach([
            $exercises[6]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 10, 'rest_seconds' => 60],
            $exercises[7]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 10, 'rest_seconds' => 60],
        ]);

        $routine4->exercises()->attach([
            $exercises[0]->id => ['day_number' => 1, 'sets' => 4, 'reps' => 12, 'rest_seconds' => 60],
            $exercises[1]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 10, 'rest_seconds' => 60],
            $exercises[2]->id => ['day_number' => 3, 'sets' => 4, 'reps' => 8, 'rest_seconds' => 90],
        ]);

        $routine5->exercises()->attach([
            $exercises[3]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 30, 'rest_seconds' => 15],
            $exercises[4]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 20, 'rest_seconds' => 20],
            $exercises[5]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 15, 'rest_seconds' => 30],
        ]);

        $routine6->exercises()->attach([
            $exercises[6]->id => ['day_number' => 1, 'sets' => 5, 'reps' => 6, 'rest_seconds' => 90], // Push day
            $exercises[7]->id => ['day_number' => 2, 'sets' => 5, 'reps' => 6, 'rest_seconds' => 90], // Pull day
            $exercises[8]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 10, 'rest_seconds' => 60],
        ]);
    }
}