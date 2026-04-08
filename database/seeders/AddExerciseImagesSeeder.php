<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;

class AddExerciseImagesSeeder extends Seeder
{
    public function run(): void
    {
        $imageUpdates = [
            // CHEST EXERCISES
            'Bench Press' => 'https://images.pexels.com/photos/2261479/pexels-photo-2261479.jpeg?w=800&auto=format',
            'Incline Bench Press' => 'https://images.pexels.com/photos/2261479/pexels-photo-2261479.jpeg?w=800&auto=format',
            'Decline Bench Press' => 'https://images.pexels.com/photos/2261479/pexels-photo-2261479.jpeg?w=800&auto=format',
            'Dumbbell Press' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Chest Fly' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Cable Crossover' => 'https://images.pexels.com/photos/4162456/pexels-photo-4162456.jpeg?w=800&auto=format',
            'Push Ups' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            
            // BACK EXERCISES
            'Deadlift' => 'https://images.pexels.com/photos/4761798/pexels-photo-4761798.jpeg?w=800&auto=format',
            'Pull Ups' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Chin Ups' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Lat Pulldown' => 'https://images.pexels.com/photos/4162456/pexels-photo-4162456.jpeg?w=800&auto=format',
            'Barbell Row' => 'https://images.pexels.com/photos/4761798/pexels-photo-4761798.jpeg?w=800&auto=format',
            'Seated Cable Row' => 'https://images.pexels.com/photos/4162456/pexels-photo-4162456.jpeg?w=800&auto=format',
            'Single Arm Dumbbell Row' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'T-Bar Row' => 'https://images.pexels.com/photos/4761798/pexels-photo-4761798.jpeg?w=800&auto=format',
            
            // LEG EXERCISES
            'Squat' => 'https://images.pexels.com/photos/4761800/pexels-photo-4761800.jpeg?w=800&auto=format',
            'Front Squat' => 'https://images.pexels.com/photos/4761800/pexels-photo-4761800.jpeg?w=800&auto=format',
            'Leg Press' => 'https://images.pexels.com/photos/4761789/pexels-photo-4761789.jpeg?w=800&auto=format',
            'Walking Lunge' => 'https://images.pexels.com/photos/4761800/pexels-photo-4761800.jpeg?w=800&auto=format',
            'Bulgarian Split Squat' => 'https://images.pexels.com/photos/4761800/pexels-photo-4761800.jpeg?w=800&auto=format',
            'Leg Curl' => 'https://images.pexels.com/photos/4761789/pexels-photo-4761789.jpeg?w=800&auto=format',
            'Leg Extension' => 'https://images.pexels.com/photos/4761789/pexels-photo-4761789.jpeg?w=800&auto=format',
            'Calf Raise' => 'https://images.pexels.com/photos/4761789/pexels-photo-4761789.jpeg?w=800&auto=format',
            
            // SHOULDER EXERCISES
            'Overhead Press' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Arnold Press' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Lateral Raise' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Front Raise' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Face Pull' => 'https://images.pexels.com/photos/4162456/pexels-photo-4162456.jpeg?w=800&auto=format',
            'Upright Row' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            
            // ARM EXERCISES
            'Barbell Curl' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Hammer Curl' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Preacher Curl' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Close Grip Bench Press' => 'https://images.pexels.com/photos/2261479/pexels-photo-2261479.jpeg?w=800&auto=format',
            'Tricep Pushdown' => 'https://images.pexels.com/photos/4162456/pexels-photo-4162456.jpeg?w=800&auto=format',
            'Dips' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Skull Crushers' => 'https://images.pexels.com/photos/2261479/pexels-photo-2261479.jpeg?w=800&auto=format',
            
            // CORE EXERCISES
            'Plank' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Hanging Leg Raise' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Russian Twist' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Cable Crunch' => 'https://images.pexels.com/photos/4162456/pexels-photo-4162456.jpeg?w=800&auto=format',
            
            // CARDIO
            'Running' => 'https://images.pexels.com/photos/2359224/pexels-photo-2359224.jpeg?w=800&auto=format',
            'Cycling' => 'https://images.pexels.com/photos/2765171/pexels-photo-2765171.jpeg?w=800&auto=format',
            'Jump Rope' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Stair Climber' => 'https://images.pexels.com/photos/4761789/pexels-photo-4761789.jpeg?w=800&auto=format',
            
            // FULL BODY
            'Burpees' => 'https://images.pexels.com/photos/4162494/pexels-photo-4162494.jpeg?w=800&auto=format',
            'Clean and Press' => 'https://images.pexels.com/photos/2261479/pexels-photo-2261479.jpeg?w=800&auto=format',
            'Farmer Walk' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
            'Kettlebell Swing' => 'https://images.pexels.com/photos/3757948/pexels-photo-3757948.jpeg?w=800&auto=format',
        ];

        foreach ($imageUpdates as $exerciseName => $imageUrl) {
            Exercise::where('name', $exerciseName)->update(['image' => $imageUrl]);
            $this->command->info("Added image to: {$exerciseName}");
        }
        
        $this->command->info("\n✅ Exercise images added successfully!");
    }
}
