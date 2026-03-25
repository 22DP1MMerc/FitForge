<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercise;

class ExercisesTableSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [

            /* ======================
               CHEST / KRŪTIS
            ====================== */

            [
                'name' => 'Bench Press',
                'description' => 'Pamats vingrinājums krūšu spēka attīstībai',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Incline Bench Press',
                'description' => 'Augšējo krūšu attīstībai',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Decline Bench Press',
                'description' => 'Apakšējo krūšu trenēšanai',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Dumbbell Press',
                'description' => 'Krūšu spēka vingrinājums ar hantelēm',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Chest Fly',
                'description' => 'Krūšu izolācijas vingrinājums',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Troses',
            ],
            [
                'name' => 'Cable Crossover',
                'description' => 'Krūšu formas uzlabošanai',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Troses',
            ],
            [
                'name' => 'Push Ups',
                'description' => 'Paša svara vingrinājums krūtīm',
                'muscle_group' => 'Krūtis',
                'equipment' => 'Ķermeņa svars',
            ],

            /* ======================
               BACK / MUGURA
            ====================== */

            [
                'name' => 'Deadlift',
                'description' => 'Pilna ķermeņa spēka vingrinājums',
                'muscle_group' => 'Mugura',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Pull Ups',
                'description' => 'Vilkšanās pie stieņa',
                'muscle_group' => 'Mugura',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Chin Ups',
                'description' => 'Vilkšanās ar apgriezto satvērienu',
                'muscle_group' => 'Mugura',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Lat Pulldown',
                'description' => 'Plato muguras muskuļu attīstīšanai',
                'muscle_group' => 'Mugura',
                'equipment' => 'Troses',
            ],
            [
                'name' => 'Barbell Row',
                'description' => 'Muguras biezuma trenēšanai',
                'muscle_group' => 'Mugura',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Seated Cable Row',
                'description' => 'Horizontāla vilkšana mugurai',
                'muscle_group' => 'Mugura',
                'equipment' => 'Troses',
            ],
            [
                'name' => 'Single Arm Dumbbell Row',
                'description' => 'Vienpusējs muguras vingrinājums',
                'muscle_group' => 'Mugura',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'T-Bar Row',
                'description' => 'Biezuma un spēka attīstīšanai',
                'muscle_group' => 'Mugura',
                'equipment' => 'T-Bar trenažieris',
            ],

            /* ======================
               LEGS / KĀJAS
            ====================== */

            [
                'name' => 'Squat',
                'description' => 'Pamata kāju vingrinājums',
                'muscle_group' => 'Kājas',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Front Squat',
                'description' => 'Kvadricepsu uzsvaram',
                'muscle_group' => 'Kājas',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Leg Press',
                'description' => 'Kāju spēka attīstīšanai',
                'muscle_group' => 'Kājas',
                'equipment' => 'Trenažieris',
            ],
            [
                'name' => 'Walking Lunge',
                'description' => 'Dinamiska kustība sēžai',
                'muscle_group' => 'Kājas',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Bulgarian Split Squat',
                'description' => 'Vienas kājas spēka vingrinājums',
                'muscle_group' => 'Kājas',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Leg Curl',
                'description' => 'Aizmugurējo augšstilbu izolācija',
                'muscle_group' => 'Kājas',
                'equipment' => 'Trenažieris',
            ],
            [
                'name' => 'Leg Extension',
                'description' => 'Priekšējo augšstilbu izolācija',
                'muscle_group' => 'Kājas',
                'equipment' => 'Trenažieris',
            ],
            [
                'name' => 'Calf Raise',
                'description' => 'Ikru muskuļu trenēšanai',
                'muscle_group' => 'Kājas',
                'equipment' => 'Hanteles',
            ],

            /* ======================
               SHOULDERS / PLECI
            ====================== */

            [
                'name' => 'Overhead Press',
                'description' => 'Plecu spēka vingrinājums',
                'muscle_group' => 'Pleci',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Arnold Press',
                'description' => 'Pilnai plecu attīstībai',
                'muscle_group' => 'Pleci',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Lateral Raise',
                'description' => 'Sānu deltu trenēšanai',
                'muscle_group' => 'Pleci',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Front Raise',
                'description' => 'Priekšējo deltu izolācija',
                'muscle_group' => 'Pleci',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Face Pull',
                'description' => 'Aizmugurējo deltu stiprināšanai',
                'muscle_group' => 'Pleci',
                'equipment' => 'Troses',
            ],
            [
                'name' => 'Upright Row',
                'description' => 'Trapeces un plecu attīstībai',
                'muscle_group' => 'Pleci',
                'equipment' => 'Stienis',
            ],

            /* ======================
               ARMS / ROKAS
            ====================== */

            [
                'name' => 'Barbell Curl',
                'description' => 'Bicepsa spēka vingrinājums',
                'muscle_group' => 'Rokas',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Hammer Curl',
                'description' => 'Brahialis trenēšanai',
                'muscle_group' => 'Rokas',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Preacher Curl',
                'description' => 'Bicepsa izolācijai',
                'muscle_group' => 'Rokas',
                'equipment' => 'EZ stienis',
            ],
            [
                'name' => 'Close Grip Bench Press',
                'description' => 'Tricepsa spēkam',
                'muscle_group' => 'Rokas',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Tricep Pushdown',
                'description' => 'Tricepsa izolācija',
                'muscle_group' => 'Rokas',
                'equipment' => 'Troses',
            ],
            [
                'name' => 'Dips',
                'description' => 'Tricepsa paša svara vingrinājums',
                'muscle_group' => 'Rokas',
                'equipment' => 'Stieņi',
            ],
            [
                'name' => 'Skull Crushers',
                'description' => 'Tricepsa guļus vingrinājums',
                'muscle_group' => 'Rokas',
                'equipment' => 'EZ stienis',
            ],

            /* ======================
               CORE / KORSETE
            ====================== */

            [
                'name' => 'Plank',
                'description' => 'Korsetes stabilitātei',
                'muscle_group' => 'Korsete',
                'equipment' => 'Ķermeņa svars',
            ],
            [
                'name' => 'Hanging Leg Raise',
                'description' => 'Apakšējās preses trenēšanai',
                'muscle_group' => 'Korsete',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Russian Twist',
                'description' => 'Slīpo vēdera muskuļu trenēšanai',
                'muscle_group' => 'Korsete',
                'equipment' => 'Hantele',
            ],
            [
                'name' => 'Cable Crunch',
                'description' => 'Vēdera prese ar svaru',
                'muscle_group' => 'Korsete',
                'equipment' => 'Troses',
            ],

            /* ======================
               CARDIO
            ====================== */

            [
                'name' => 'Running',
                'description' => 'Izturības uzlabošanai',
                'muscle_group' => 'Kardio',
                'equipment' => 'Nav',
            ],
            [
                'name' => 'Cycling',
                'description' => 'Velotreniņš',
                'muscle_group' => 'Kardio',
                'equipment' => 'Velotrenažieris',
            ],
            [
                'name' => 'Jump Rope',
                'description' => 'Lēcieni ar lecamauklu',
                'muscle_group' => 'Kardio',
                'equipment' => 'Lecamauklas',
            ],
            [
                'name' => 'Stair Climber',
                'description' => 'Kāpšana pa kāpnēm',
                'muscle_group' => 'Kardio',
                'equipment' => 'Trenažieris',
            ],

            /* ======================
               FULL BODY
            ====================== */

            [
                'name' => 'Burpees',
                'description' => 'Eksplozīvs visa ķermeņa vingrinājums',
                'muscle_group' => 'Pilns ķermenis',
                'equipment' => 'Ķermeņa svars',
            ],
            [
                'name' => 'Clean and Press',
                'description' => 'Olimpiska tipa vingrinājums',
                'muscle_group' => 'Pilns ķermenis',
                'equipment' => 'Stienis',
            ],
            [
                'name' => 'Farmer Walk',
                'description' => 'Satvēriena un spēka uzlabošanai',
                'muscle_group' => 'Pilns ķermenis',
                'equipment' => 'Hanteles',
            ],
            [
                'name' => 'Kettlebell Swing',
                'description' => 'Sprādziena spēka vingrinājums',
                'muscle_group' => 'Pilns ķermenis',
                'equipment' => 'Svarbumba',
            ],
        ];

        foreach ($exercises as $exercise) {
            Exercise::create($exercise);
        }
    }
}
