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
        $user = User::firstOrCreate([
            'email' => 'test@example.com'
        ], [
            'name' => 'Testa Lietotājs',
            'password' => bcrypt('password')
        ]);

        $exercises = Exercise::limit(15)->get();

        if ($exercises->isEmpty()) {
            $exercises = Exercise::factory()->count(15)->create();
        }

        // Routines
        $routine1 = Routine::create([
            'name' => 'Iesācēju Pilna Ķermeņa Treniņš',
            'description' => 'Vienkāršs treniņš visam ķermenim iesācējiem',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $routine2 = Routine::create([
            'name' => 'Push Pull Legs Advanced',
            'description' => '6 dienu sadalījums pieredzējušiem sportistiem',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $routine3 = Routine::create([
            'name' => 'Mans Personīgais Plāns',
            'description' => 'Individuāls treniņš maniem mērķiem',
            'user_id' => $user->id,
            'is_public' => false
        ]);

        $routine4 = Routine::create([
            'name' => 'Hipertrofijas Programma',
            'description' => 'Muskuļu masas palielināšanai',
            'user_id' => $user->id,
            'is_public' => true
        ]);

        $routine5 = Routine::create([
            'name' => 'Kardio un Korsete',
            'description' => 'Tauku dedzināšanai un izturībai',
            'user_id' => $user->id,
            'is_public' => false
        ]);

        // Attach exercises (no rest_seconds)

        $routine1->exercises()->attach([
            $exercises[0]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 10],
            $exercises[1]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 12],
            $exercises[2]->id => ['day_number' => 2, 'sets' => 3, 'reps' => 8],
        ]);

        $routine2->exercises()->attach([
            $exercises[3]->id => ['day_number' => 1, 'sets' => 4, 'reps' => 6],
            $exercises[4]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 8],
            $exercises[5]->id => ['day_number' => 3, 'sets' => 5, 'reps' => 5],
        ]);

        $routine3->exercises()->attach([
            $exercises[6]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 10],
            $exercises[7]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 10],
        ]);

        $routine4->exercises()->attach([
            $exercises[8]->id => ['day_number' => 1, 'sets' => 4, 'reps' => 12],
            $exercises[9]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 10],
            $exercises[10]->id => ['day_number' => 3, 'sets' => 4, 'reps' => 8],
        ]);

        $routine5->exercises()->attach([
            $exercises[11]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 25],
            $exercises[12]->id => ['day_number' => 1, 'sets' => 3, 'reps' => 20],
            $exercises[13]->id => ['day_number' => 2, 'sets' => 4, 'reps' => 15],
        ]);
    }
}
