<?php
namespace App\Http\Controllers;

use App\Models\Routine;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoutineController extends Controller
{
    // viena rutīna — publiska vai lietotāja pašu
    public function show(Routine $routine)
    {
        $user = auth()->user();

        if (!$routine->is_public && (!$user || $routine->user_id !== $user->id)) {
            abort(403);
        }

        $routine->load(['exercises' => function ($query) {
            $query->orderBy('exercise_routine.day_number');
        }]);

        return inertia('Routines/Show', [
            'routine'  => $routine,
            'weekDays' => $this->weekDays(),
        ]);
    }

    // jauna rutīna — forma
    public function create()
    {
        return inertia('Routines/Create', [
            'exercises' => Exercise::all(),
            'weekDays'  => $this->weekDays(),
        ]);
    }

    // saglabā jaunu rutīnu
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string',
            'is_public'                => 'boolean',
            'exercises'                => 'required|array|min:1',
            'exercises.*.id'           => 'required|exists:exercises,id',
            'exercises.*.day_number'   => 'required|integer|min:1|max:7',
            'exercises.*.sets'         => 'required|integer|min:1',
            'exercises.*.reps'         => 'required|integer|min:1',
            'exercises.*.notes'        => 'nullable|string',
        ]);

        $routine = $request->user()->routines()->create([
            'name'        => $validated['name'],
            'description' => $validated['description'],
            'is_public'   => $validated['is_public'] ?? false,
        ]);

        $routine->exercises()->attach(
            collect($validated['exercises'])
                ->mapWithKeys(fn ($e) => [
                    $e['id'] => [
                        'day_number' => $e['day_number'],
                        'sets'       => $e['sets'],
                        'reps'       => $e['reps'],
                        'notes'      => $e['notes'],
                    ]
                ])
        );

        return redirect()->route('routines.show', $routine);
    }

    // visas rutīnas — publiskās + lietotāja pašu
    public function index()
    {
        $user = auth()->user();

        $routines = Routine::with(['user', 'exercises'])
            ->where(function ($query) use ($user) {
                $query->where('is_public', true);
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->latest()
            ->get();

        return Inertia::render('Routines/Routineview', [
            'routines' => $routines,
        ]);
    }

    // tikai publiskās rutīnas
    public function publicIndex()
    {
        $routines = Routine::where('is_public', true)
            ->with(['user', 'exercises'])
            ->latest()
            ->get();

        return Inertia::render('Routines/Public', [
            'routines' => $routines,
        ]);
    }

    // lietotāja pašu rutīnas
    public function myRoutines()
    {
        $routines = Routine::with(['user', 'exercises'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('Routines/Routineview', [
            'routines' => $routines,
        ]);
    }

    // rutīna JSON formātā (API)
    public function getRoutine(Routine $routine)
    {
        $user = auth()->user();

        if ($routine->user_id !== $user->id && !$routine->is_public) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $routine->load(['exercises' => function ($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'notes']);
        }]);

        return response()->json($routine);
    }

    // iestata aktīvo rutīnu
    public function setActive(Routine $routine)
    {
        $user = auth()->user();

        if ($routine->user_id !== $user->id && !$routine->is_public) {
            return response()->json(['error' => 'Nav pieejas šai rutīnai'], 403);
        }

        $user->active_routine_id = $routine->id;
        $user->save();

        $routine->load(['exercises' => function ($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'notes'])
                  ->orderBy('exercise_routine.day_number');
        }]);

        return response()->json([
            'success' => true,
            'routine' => $this->formatRoutine($routine),
        ]);
    }

    // notīra aktīvo rutīnu
    public function clearActive()
    {
        $user = auth()->user();
        $user->active_routine_id = null;
        $user->save();

        return response()->json(['success' => true]);
    }

    // atgriež pašreizējo aktīvo rutīnu
    public function getActiveRoutine()
    {
        $user = auth()->user();
        $routine = $user->activeRoutine;

        if (!$routine) {
            return response()->json(['success' => true, 'active_routine' => null]);
        }

        $routine->load(['exercises' => function ($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'notes'])
                  ->orderBy('exercise_routine.day_number');
        }]);

        return response()->json([
            'success'        => true,
            'active_routine' => $this->formatRoutine($routine),
        ]);
    }

    // rediģēšanas forma
    public function edit(Routine $routine)
    {
        $user = auth()->user();

        if ($routine->user_id !== $user->id) {
            abort(403);
        }

        $routine->load(['exercises' => function ($query) {
            $query->withPivot(['day_number', 'sets', 'reps', 'notes']);
        }]);

        return inertia('Routines/Edit', [
            'routine'   => $routine,
            'exercises' => Exercise::all(),
            'weekDays'  => $this->weekDays(),
        ]);
    }

    // saglabā izmaiņas
    public function update(Request $request, Routine $routine)
    {
        $user = auth()->user();

        if ($routine->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'                   => 'required|string|max:255',
            'description'            => 'nullable|string',
            'is_public'              => 'boolean',
            'exercises'              => 'nullable|array',
            'exercises.*.id'         => 'required|exists:exercises,id',
            'exercises.*.day_number' => 'required|integer|min:1|max:7',
            'exercises.*.sets'       => 'required|integer|min:1',
            'exercises.*.reps'       => 'required|integer|min:1',
            'exercises.*.notes'      => 'nullable|string',
        ]);

        $routine->update([
            'name'        => $validated['name'],
            'description' => $validated['description'],
            'is_public'   => $validated['is_public'] ?? false,
        ]);

        if (isset($validated['exercises'])) {
            $routine->exercises()->detach();

            $routine->exercises()->attach(
                collect($validated['exercises'])
                    ->mapWithKeys(fn ($e) => [
                        $e['id'] => [
                            'day_number' => $e['day_number'],
                            'sets'       => $e['sets'],
                            'reps'       => $e['reps'],
                            'notes'      => $e['notes'],
                        ]
                    ])
            );
        }

        return redirect()->route('routines.my')->with('success', 'Rutīna veiksmīgi atjaunināta!');
    }

    // dzēš rutīnu
    public function destroy(Routine $routine)
    {
        $user = auth()->user();

        // admins var dzēst jebkuru
        if (!$user->is_admin && $routine->user_id !== $user->id) {
            abort(403);
        }

        if ($user->active_routine_id === $routine->id) {
            $user->update(['active_routine_id' => null]);
        }

        $routine->delete();
        return redirect()->route('routines.my')->with('success', 'Rutīna dzēsta!');
    }

    // kopīgs formāts rutīnas atbildei
    private function formatRoutine(Routine $routine): array
    {
        return [
            'id'              => $routine->id,
            'name'            => $routine->name,
            'description'     => $routine->description,
            'is_public'       => $routine->is_public,
            'user_id'         => $routine->user_id,
            'exercises_count' => $routine->exercises->count(),
            'exercises'       => $routine->exercises->map(fn ($e) => [
                'id'           => $e->id,
                'name'         => $e->name,
                'muscle_group' => $e->muscle_group,
                'description'  => $e->description ?? null,
                'day_number'   => $e->pivot->day_number,
                'sets'         => $e->pivot->sets,
                'reps'         => $e->pivot->reps,
                'notes'        => $e->pivot->notes,
            ])->toArray(),
        ];
    }

    // nedēļas dienas latviski
    private function weekDays(): array
    {
        return [
            1 => ['id' => 1, 'name' => 'Pirmdiena'],
            2 => ['id' => 2, 'name' => 'Otrdiena'],
            3 => ['id' => 3, 'name' => 'Trešdiena'],
            4 => ['id' => 4, 'name' => 'Ceturtdiena'],
            5 => ['id' => 5, 'name' => 'Piektdiena'],
            6 => ['id' => 6, 'name' => 'Sestdiena'],
            7 => ['id' => 7, 'name' => 'Svētdiena'],
        ];
    }
}
