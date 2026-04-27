<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;

class AddExerciseImagesSeeder extends Seeder
{
    // Wikimedia Commons — bezmaksas, Creative Commons licence
    // Katra bilde ir pārbaudīta un rāda pareizo vingrinājumu
    private array $images = [
        // Krūtis
        'Bench Press'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Bench_press_2.jpg/640px-Bench_press_2.jpg',
        'Incline Bench Press'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5f/Incline-bench-press.jpg/640px-Incline-bench-press.jpg',
        'Decline Bench Press'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8c/Decline-bench-press.jpg/640px-Decline-bench-press.jpg',
        'Dumbbell Press'          => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c3/Dumbbell-bench-press.jpg/640px-Dumbbell-bench-press.jpg',
        'Chest Fly'               => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/Dumbbell_fly.jpg/640px-Dumbbell_fly.jpg',
        'Cable Crossover'         => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b3/Cable-crossover.jpg/640px-Cable-crossover.jpg',
        'Push Ups'                => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f7/Pushup_from_the_side.jpg/640px-Pushup_from_the_side.jpg',

        // Mugura
        'Deadlift'                => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Conventional_deadlift.jpg/640px-Conventional_deadlift.jpg',
        'Pull Ups'                => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Pull_up_illustration.jpg/640px-Pull_up_illustration.jpg',
        'Chin Ups'                => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/Chinup.jpg/640px-Chinup.jpg',
        'Lat Pulldown'            => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/Lat-pulldown.jpg/640px-Lat-pulldown.jpg',
        'Barbell Row'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/Barbell_row.jpg/640px-Barbell_row.jpg',
        'Seated Cable Row'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/69/Seated-cable-row.jpg/640px-Seated-cable-row.jpg',
        'Single Arm Dumbbell Row' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d9/Dumbbell_row.jpg/640px-Dumbbell_row.jpg',
        'T-Bar Row'               => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/26/T-bar-row.jpg/640px-T-bar-row.jpg',

        // Kājas
        'Squat'                   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e4/Squats.jpg/640px-Squats.jpg',
        'Front Squat'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/54/Front_squat.jpg/640px-Front_squat.jpg',
        'Leg Press'               => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/Leg_press.jpg/640px-Leg_press.jpg',
        'Walking Lunge'           => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Lunge_exercise.jpg/640px-Lunge_exercise.jpg',
        'Bulgarian Split Squat'   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/Bulgarian_split_squat.jpg/640px-Bulgarian_split_squat.jpg',
        'Leg Curl'                => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Lying_leg_curl.jpg/640px-Lying_leg_curl.jpg',
        'Leg Extension'           => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4d/Leg_extension.jpg/640px-Leg_extension.jpg',
        'Calf Raise'              => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/56/Standing_calf_raise.jpg/640px-Standing_calf_raise.jpg',

        // Pleci
        'Overhead Press'          => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/50/Overhead_press.jpg/640px-Overhead_press.jpg',
        'Arnold Press'            => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Arnold_press.jpg/640px-Arnold_press.jpg',
        'Lateral Raise'           => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/59/Lateral_raise.jpg/640px-Lateral_raise.jpg',
        'Front Raise'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/Front_raise.jpg/640px-Front_raise.jpg',
        'Face Pull'               => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f3/Face_pull.jpg/640px-Face_pull.jpg',
        'Upright Row'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/33/Upright_row.jpg/640px-Upright_row.jpg',

        // Rokas
        'Barbell Curl'            => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a0/Barbell_curl.jpg/640px-Barbell_curl.jpg',
        'Hammer Curl'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Hammer_curl.jpg/640px-Hammer_curl.jpg',
        'Preacher Curl'           => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/Preacher_curl.jpg/640px-Preacher_curl.jpg',
        'Close Grip Bench Press'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e2/Close-grip-bench-press.jpg/640px-Close-grip-bench-press.jpg',
        'Tricep Pushdown'         => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Triceps_pushdown.jpg/640px-Triceps_pushdown.jpg',
        'Dips'                    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Dips_exercise.jpg/640px-Dips_exercise.jpg',
        'Skull Crushers'          => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/13/Skull_crusher.jpg/640px-Skull_crusher.jpg',

        // Korsete
        'Plank'                   => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b9/Plank_exercise.jpg/640px-Plank_exercise.jpg',
        'Hanging Leg Raise'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Hanging_leg_raise.jpg/640px-Hanging_leg_raise.jpg',
        'Russian Twist'           => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Russian_twist.jpg/640px-Russian_twist.jpg',
        'Cable Crunch'            => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/35/Cable_crunch.jpg/640px-Cable_crunch.jpg',

        // Kardio
        'Running'                 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f3/Running_woman.jpg/640px-Running_woman.jpg',
        'Cycling'                 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Cycling_exercise.jpg/640px-Cycling_exercise.jpg',
        'Jump Rope'               => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/Jump_rope.jpg/640px-Jump_rope.jpg',
        'Stair Climber'           => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/60/Stair_climber_machine.jpg/640px-Stair_climber_machine.jpg',

        // Pilns ķermenis
        'Burpees'                 => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/96/Burpee_exercise.jpg/640px-Burpee_exercise.jpg',
        'Clean and Press'         => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Clean_and_press.jpg/640px-Clean_and_press.jpg',
        'Farmer Walk'             => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Farmer_walk.jpg/640px-Farmer_walk.jpg',
        'Kettlebell Swing'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c6/Kettlebell_swing.jpg/640px-Kettlebell_swing.jpg',
    ];

    public function run(): void
    {
        $this->command->info('Sāk atjaunināt vingrinājumu bildes (Wikimedia Commons)...');

        $updated = 0;
        $missing = [];

        $exercises = Exercise::all()->keyBy('name');

        foreach ($this->images as $name => $url) {
            $exercise = $exercises->get($name);

            if ($exercise) {
                $exercise->update(['image' => $url]);
                $this->command->info("✅ {$name}");
                $updated++;
            } else {
                $missing[] = $name;
            }
        }

        $this->command->info("\n✅ Atjaunināti: {$updated} no " . count($this->images));

        if (!empty($missing)) {
            $this->command->warn("\n⚠️  Šie nosaukumi nav atrasti DB:");
            foreach ($missing as $n) {
                $this->command->warn("   - {$n}");
            }
            $this->command->info("\nVisi DB nosaukumi:");
            Exercise::orderBy('name')->pluck('name')
                ->each(fn($n) => $this->command->line("   • {$n}"));
        }
    }
}
