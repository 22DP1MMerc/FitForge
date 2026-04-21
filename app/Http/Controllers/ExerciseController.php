<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExerciseController extends Controller
{
    // Palīgfunkcija — atgriež sarakstu formu lapām
    private function formOptions(): array
    {
        return [
            'muscleGroups'     => Exercise::distinct()->orderBy('muscle_group')->pluck('muscle_group'),
            'equipmentOptions' => Exercise::distinct()->orderBy('equipment')->pluck('equipment'),
            'difficulties'     => ['Viegls', 'Vidējs', 'Grūts'],
            'types'            => ['strength', 'cardio'],
        ];
    }

    // Vingrinājumu saraksts ar filtriem
    public function index(Request $request)
    {
        $query = Exercise::query();

        if ($request->filled('muscle_group')) {
            $query->where('muscle_group', $request->muscle_group);
        }

        if ($request->filled('equipment')) {
            $query->where('equipment', $request->equipment);
        }

        $exercises = $query->orderBy('muscle_group')->orderBy('name')->get();

        // Ielādē personīgos rekordus ja ielogojies
        if (Auth::check()) {
            $exercises->load(['personalRecords' => function ($query) {
                $query->where('user_id', Auth::id())
                      ->orderBy('weight', 'desc')
                      ->orderBy('reps', 'desc');
            }]);
        }

        // Pārbauda image_url atribūtu
        $exercises->each(fn ($ex) => $ex->image_url);

        return Inertia::render('Exercview', [
            'exercises'        => $exercises,
            'muscleGroups'     => Exercise::distinct()->orderBy('muscle_group')->pluck('muscle_group'),
            'equipmentOptions' => Exercise::distinct()->orderBy('equipment')->pluck('equipment'),
            'filters'          => $request->only(['muscle_group', 'equipment']),
        ]);
    }

    // Admin: jauna vingrinājuma forma
    public function create()
    {
        $this->authorizeAdmin();

        return Inertia::render('Exercises/Create', $this->formOptions());
    }

    // Admin: saglabā jaunu vingrinājumu
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:exercises,name',
            'description'  => 'nullable|string|max:1000',
            'muscle_group' => 'required|string|max:100',
            'equipment'    => 'required|string|max:100',
            'type'         => 'nullable|in:strength,cardio',
            'difficulty'   => 'nullable|string|max:50',
            'instructions' => 'nullable|string',
            'image'        => 'nullable|url|max:500',
        ]);

        Exercise::create($validated);

        return redirect()->route('exercises.index')
            ->with('success', "Vingrinājums \"{$validated['name']}\" pievienots!");
    }

    // Admin: rediģēšanas forma
    public function edit(Exercise $exercise)
    {
        $this->authorizeAdmin();

        return Inertia::render('Exercises/Edit', array_merge(
            ['exercise' => $exercise],
            $this->formOptions()
        ));
    }

    // Admin: saglabā izmaiņas
    public function update(Request $request, Exercise $exercise)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:exercises,name,' . $exercise->id,
            'description'  => 'nullable|string|max:1000',
            'muscle_group' => 'required|string|max:100',
            'equipment'    => 'required|string|max:100',
            'type'         => 'nullable|in:strength,cardio',
            'difficulty'   => 'nullable|string|max:50',
            'instructions' => 'nullable|string',
            'image'        => 'nullable|url|max:500',
        ]);

        $exercise->update($validated);

        return redirect()->route('exercises.index')
            ->with('success', "Vingrinājums \"{$exercise->name}\" atjaunināts!");
    }

    // Admin: dzēš vingrinājumu
    public function destroy(Exercise $exercise)
    {
        $this->authorizeAdmin();

        $name = $exercise->name;
        $exercise->delete();

        return redirect()->route('exercises.index')
            ->with('success', "Vingrinājums \"{$name}\" dzēsts!");
    }

    // API filtrs priekš FreeWorkout meklēšanas
    public function filter(Request $request)
    {
        $query = Exercise::query();

        if ($request->filled('muscle_group')) {
            $query->where('muscle_group', $request->muscle_group);
        }
        if ($request->filled('equipment')) {
            $query->where('equipment', $request->equipment);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(
            $query->orderBy('name')->get(['id', 'name', 'muscle_group', 'equipment', 'type'])
        );
    }

    // Pārbauda vai lietotājs ir admins
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->is_admin) {
            abort(403, 'Tikai administratori var veikt šo darbību.');
        }
    }
}
