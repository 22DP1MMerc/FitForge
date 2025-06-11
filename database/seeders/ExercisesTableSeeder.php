<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;

class ExercisesTableSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            // Chest (new)
            [
                'name' => 'Chest Fly',
                'description' => 'Isolation exercise using cable or dumbbells for chest muscles',
                'muscle_group' => 'Chest',
                'equipment' => 'Cable Machine',
            ],
            [
                'name' => 'Decline Bench Press',
                'description' => 'Barbell press targeting lower chest',
                'muscle_group' => 'Chest',
                'equipment' => 'Barbell',
            ],

            // Legs (new)
            [
                'name' => 'Leg Curl',
                'description' => 'Isolation machine exercise for hamstrings',
                'muscle_group' => 'Legs',
                'equipment' => 'Machine',
            ],
            [
                'name' => 'Walking Lunge',
                'description' => 'Dynamic lower body movement targeting glutes and quads',
                'muscle_group' => 'Legs',
                'equipment' => 'Dumbbells',
            ],

            // Back (new)
            [
                'name' => 'T-Bar Row',
                'description' => 'Compound movement emphasizing back thickness',
                'muscle_group' => 'Back',
                'equipment' => 'T-Bar Row Machine',
            ],
            [
                'name' => 'Single Arm Dumbbell Row',
                'description' => 'Isolation movement for lats and rhomboids',
                'muscle_group' => 'Back',
                'equipment' => 'Dumbbells',
            ],

            // Shoulders (new)
            [
                'name' => 'Arnold Press',
                'description' => 'Shoulder press variation that targets all deltoid heads',
                'muscle_group' => 'Shoulders',
                'equipment' => 'Dumbbells',
            ],
            [
                'name' => 'Face Pull',
                'description' => 'Cable exercise focusing on rear delts and traps',
                'muscle_group' => 'Shoulders',
                'equipment' => 'Cable Machine',
            ],

            // Core (new)
            [
                'name' => 'Bicycle Crunch',
                'description' => 'Rotational core exercise for obliques',
                'muscle_group' => 'Core',
                'equipment' => 'Bodyweight',
            ],
            [
                'name' => 'Cable Crunch',
                'description' => 'Weighted abdominal crunch using cable machine',
                'muscle_group' => 'Core',
                'equipment' => 'Cable Machine',
            ],

            // Arms (new)
            [
                'name' => 'Hammer Curl',
                'description' => 'Dumbbell curl variation targeting brachialis',
                'muscle_group' => 'Arms',
                'equipment' => 'Dumbbells',
            ],
            [
                'name' => 'Skull Crushers',
                'description' => 'Lying triceps extension with barbell or EZ bar',
                'muscle_group' => 'Arms',
                'equipment' => 'EZ Bar',
            ],

            // Cardio (new)
            [
                'name' => 'Rowing Machine',
                'description' => 'Full-body cardio exercise using rowing ergometer',
                'muscle_group' => 'Cardio',
                'equipment' => 'Rowing Machine',
            ],
            [
                'name' => 'Cycling',
                'description' => 'Cardiovascular workout on a stationary bike or outdoors',
                'muscle_group' => 'Cardio',
                'equipment' => 'Bike',
            ],

            // Full Body (new)
            [
                'name' => 'Clean and Press',
                'description' => 'Olympic lift combining clean and overhead press',
                'muscle_group' => 'Full Body',
                'equipment' => 'Barbell',
            ],
            [
                'name' => 'Kettlebell Swing',
                'description' => 'Explosive hip hinge movement for power and endurance',
                'muscle_group' => 'Full Body',
                'equipment' => 'Kettlebell',
            ],

            // Mobility/Functional (new)
            [
                'name' => 'Bodyweight Lateral Lunge',
                'description' => 'Mobility-focused movement to improve hip and groin flexibility',
                'muscle_group' => 'Legs',
                'equipment' => 'Bodyweight',
            ],
            [
                'name' => 'Inchworm Walkout',
                'description' => 'Dynamic warm-up targeting hamstrings, shoulders, and core',
                'muscle_group' => 'Full Body',
                'equipment' => 'Bodyweight',
            ],
        ];

        foreach ($exercises as $exercise) {
            Exercise::create($exercise);
        }
    }
}
