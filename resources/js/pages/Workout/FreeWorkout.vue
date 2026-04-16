<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import { useModal } from '@/Composables/useModal';

const { modalRef, confirm, alert, success, error } = useModal();

// ========== PROPS ==========
const props = defineProps({
    availableExercises: { type: Array,  default: () => [] },
    initialWorkout:     { type: Object, default: () => ({}) },
    workoutSession:     { type: Object, default: null }
});

// ========== HRONOMETRS ==========
const timer        = ref(0);
const timerRunning = ref(false);
let timerInterval: NodeJS.Timeout | null = null;

const pad = (n: number) => n.toString().padStart(2, '0');

const formattedTime = computed(() => {
    const h = Math.floor(timer.value / 3600);
    const m = Math.floor((timer.value % 3600) / 60);
    const s = timer.value % 60;
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
});

const toggleTimer = () => {
    if (timerRunning.value) {
        clearInterval(timerInterval!);
        timerInterval      = null;
        timerRunning.value = false;
    } else {
        timerRunning.value = true;
        timerInterval      = setInterval(() => timer.value++, 1000);
    }
};

const calculateElapsed = (startTime: string) =>
    !startTime ? 0 : Math.floor((Date.now() - new Date(startTime).getTime()) / 1000);

// ========== TRENIŅA STĀVOKLIS ==========
const workoutName      = ref(props.initialWorkout?.name || 'Brīvais treniņš');
const workoutSessionId = ref(props.workoutSession?.id || null);
const exercises        = ref<any[]>([]);

// ========== MEKLĒŠANA ==========
const searchOpen           = ref(false);
const searchQuery          = ref('');
const selectedMuscleGroups = ref<string[]>([]);

const muscleGroups = computed(() => {
    const g = new Set<string>();
    props.availableExercises.forEach((e: any) => { if (e.muscle_group) g.add(e.muscle_group); });
    return Array.from(g).sort();
});

const filteredExercises = computed(() =>
    props.availableExercises.filter((e: any) => {
        const matchSearch = !searchQuery.value ||
            e.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchGroup = !selectedMuscleGroups.value.length ||
            selectedMuscleGroups.value.includes(e.muscle_group);
        return matchSearch && matchGroup;
    })
);

const toggleMuscleGroup = (g: string) => {
    const i = selectedMuscleGroups.value.indexOf(g);
    i > -1 ? selectedMuscleGroups.value.splice(i, 1) : selectedMuscleGroups.value.push(g);
};

const openSearch  = () => { searchQuery.value = ''; searchOpen.value = true; };
const closeSearch = () => { searchOpen.value = false; };

// ========== SESIJA ==========
const loadSession = async () => {
    if (!workoutSessionId.value) return;
    try {
        const r = await axios.get(`/api/workout-session/${workoutSessionId.value}`);
        if (!r.data) return;
        workoutName.value = r.data.name || workoutName.value;
        exercises.value   = r.data.exercises.map((ex: any) => ({
            id:                  ex.id,
            session_exercise_id: ex.session_exercise_id,
            name:                ex.name,
            muscle_group:        ex.muscle_group,
            sets: [
                ...(ex.reps_completed || []).map((reps: number, i: number) => ({
                    reps, weight: ex.weights_used?.[i] || 0, done: true
                })),
                ...Array.from(
                    { length: Math.max(0, ex.sets_planned - (ex.reps_completed?.length || 0)) },
                    () => ({ reps: 10, weight: 0, done: false })
                )
            ]
        }));
        if (r.data.started_at) {
            timer.value = calculateElapsed(r.data.started_at);
            if (!timerRunning.value) toggleTimer();
        }
    } catch (e) { console.error('Kļūda ielādējot sesiju:', e); }
};

const ensureSession = async (): Promise<boolean> => {
    if (workoutSessionId.value) return true;
    try {
        const r = await axios.post('/workout/free/start', { name: workoutName.value });
        if (r.data?.success) {
            workoutSessionId.value = r.data.session_id;
            localStorage.setItem('workout_start_time', new Date().toISOString());
            if (!timerRunning.value) toggleTimer();
            return true;
        }
    } catch (e) { console.error(e); }
    return false;
};

// ========== VINGRINĀJUMA PIEVIENOŠANA ==========
const addExercise = async (exercise: any) => {
    if (exercises.value.find((e: any) => e.id === exercise.id)) {
        closeSearch();
        return;
    }
    const ok = await ensureSession();
    if (!ok) return;
    try {
        const r = await axios.post(`/workout/${workoutSessionId.value}/exercises`, {
            exercise_id:  exercise.id,
            sets_planned: 3,
            reps_planned: 10
        });
        exercises.value.push({
            id:                  exercise.id,
            session_exercise_id: r.data.session_exercise_id ?? null,
            name:                exercise.name,
            muscle_group:        exercise.muscle_group,
            sets: [
                { reps: 10, weight: 0, done: false },
                { reps: 10, weight: 0, done: false },
                { reps: 10, weight: 0, done: false }
            ]
        });
        closeSearch();
        await loadSession();
    } catch (e) { console.error(e); }
};

const removeExercise = async (exIndex: number) => {
    const ex = exercises.value[exIndex];
    try {
        if (workoutSessionId.value && ex.session_exercise_id)
            await axios.delete(`/workout/${workoutSessionId.value}/exercises/${ex.session_exercise_id}`);
        exercises.value.splice(exIndex, 1);
    } catch (e) {
        console.error(e);
        await error('Kļūda noņemot vingrinājumu');
    }
};

// ========== SETU DARBĪBAS ==========
const completeSet = async (exIndex: number, setIndex: number) => {
    const ex  = exercises.value[exIndex];
    const set = ex.sets[setIndex];
    set.done  = true;
    try {
        if (workoutSessionId.value && ex.session_exercise_id)
            await axios.post(
                `/workout/${workoutSessionId.value}/exercises/${ex.session_exercise_id}/set`,
                { set_index: setIndex, reps: set.reps, weight: set.weight }
            );
    } catch (e) {
        console.error('Kļūda saglabājot setu:', e);
        set.done = false;
    }
};

const undoSet   = (exIndex: number, setIndex: number) => { exercises.value[exIndex].sets[setIndex].done = false; };
const removeSet = (exIndex: number, setIndex: number) => { exercises.value[exIndex].sets.splice(setIndex, 1); };
const addSet    = (exIndex: number) => {
    const last = [...exercises.value[exIndex].sets].reverse().find((s: any) => s.done);
    exercises.value[exIndex].sets.push({ reps: last?.reps ?? 10, weight: last?.weight ?? 0, done: false });
};

// ========== STATISTIKA ==========
const totalSets = computed(() => exercises.value.reduce((s: number, ex: any) => s + ex.sets.length, 0));
const doneSets  = computed(() => exercises.value.reduce((s: number, ex: any) => s + ex.sets.filter((set: any) => set.done).length, 0));

// ========== PABEIGT / ATCELT ==========
const completeWorkout = async () => {
    if (!exercises.value.length) {
        await alert({ title: 'Nav vingrinājumu', message: 'Pievieno vismaz vienu vingrinājumu!', type: 'error' });
        return;
    }
    if (!doneSets.value) {
        await alert({ title: 'Nav pabeigtu setu', message: 'Pabeidz vismaz vienu setu!', type: 'error' });
        return;
    }
    const confirmed = await confirm({
        title: 'Pabeigt treniņu?',
        message: 'Vai tiešām vēlies pabeigt treniņu?',
        details: {
            'Nosaukums': workoutName.value,
            'Ilgums':    formattedTime.value,
            'Seti':      `${doneSets.value}/${totalSets.value}`
        },
        confirmText: 'Pabeigt',
        cancelText:  'Turpināt'
    });
    if (!confirmed) return;
    if (timerRunning.value) toggleTimer();
    try {
        const r = await axios.post(`/workout/${workoutSessionId.value}/complete`, {
            duration_minutes: Math.max(1, Math.round(timer.value / 60)),
            notes: ''
        });
        if (r.data.success) {
            await success('Treniņš pabeigts!');
            localStorage.removeItem('workout_start_time');
            router.visit('/dashboard');
        }
    } catch (e: any) {
        await error('Kļūda: ' + (e.response?.data?.message || e.message));
    }
};

const cancelWorkout = async () => {
    const confirmed = await confirm({
        title: 'Atcelt treniņu?',
        message: 'Vai tiešām vēlies atcelt treniņu? Visi dati tiks zaudēti.',
        confirmText: 'Jā, atcelt',
        cancelText: 'Nē'
    });
    if (!confirmed) return;
    try {
        if (workoutSessionId.value)
            await axios.post(`/workout/${workoutSessionId.value}/cancel`);
        localStorage.removeItem('workout_start_time');
        router.visit('/dashboard');
    } catch (e: any) {
        await error('Kļūda: ' + (e.response?.data?.message || e.message));
    }
};

// ========== LIFECYCLE ==========
onMounted(() => {
    if (props.workoutSession) {
        workoutSessionId.value = props.workoutSession.id;
        loadSession();
        if (props.workoutSession.started_at)
            timer.value = calculateElapsed(props.workoutSession.started_at);
        if (!timerRunning.value) toggleTimer();
    } else {
        const saved = localStorage.getItem('workout_start_time');
        if (saved && calculateElapsed(saved) < 86400) {
            timer.value = calculateElapsed(saved);
            if (!timerRunning.value) toggleTimer();
        }
    }
});

watch(timer, () => {
    if (timerRunning.value && timer.value % 30 === 0)
        localStorage.setItem('workout_timer', timer.value.toString());
});

onUnmounted(() => { if (timerInterval) clearInterval(timerInterval); });
</script>

<template>
    <AppLayout>
        <div class="page">

            <!-- Galvene -->
            <div class="topbar">
                <div class="topbar-left">
                    <input v-model="workoutName" class="workout-title-input" placeholder="Treniņa nosaukums" />
                    <span class="topbar-date">
                        {{ new Date().toLocaleDateString('lv-LV', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                    </span>
                </div>
                <div class="topbar-center">
                    <div class="timer-wrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15" style="flex-shrink:0;color:rgba(255,255,255,0.8)">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="timer-display">{{ formattedTime }}</span>
                        <button @click="toggleTimer" class="timer-toggle" :class="{ 'timer-paused': !timerRunning }">
                            {{ timerRunning ? '⏸' : '▶' }}
                        </button>
                    </div>
                </div>
                <div class="topbar-right">
                    <button @click="cancelWorkout" class="btn-cancel">Atcelt</button>
                    <button @click="completeWorkout" class="btn-finish">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        Pabeigt
                    </button>
                </div>
            </div>

            <!-- Progresa josla -->
            <div class="progress-wrap">
                <div class="progress-fill"
                     :style="{ width: totalSets ? (doneSets / totalSets * 100) + '%' : '0%' }"></div>
            </div>

            <!-- Vingrinājumu saraksts -->
            <div class="exercises-list">

                <div v-for="(ex, exIndex) in exercises" :key="ex.id" class="exercise-card">

                    <!-- Virsraksts -->
                    <div class="exercise-header">
                        <div class="exercise-header-info">
                            <span class="exercise-name">{{ ex.name }}</span>
                            <span class="exercise-muscle-badge">{{ ex.muscle_group }}</span>
                        </div>
                        <button @click="removeExercise(exIndex)" class="btn-remove-ex" title="Noņemt">✕</button>
                    </div>

                    <!-- Kolonnu galvene -->
                    <div class="sets-header">
                        <span class="col-num">#</span>
                        <span class="col-prev">Iepriekš</span>
                        <span class="col-kg">kg</span>
                        <span class="col-reps">Reiz.</span>
                        <span class="col-action"></span>
                    </div>

                    <!-- Setu rindas -->
                    <div v-for="(set, setIndex) in ex.sets"
                         :key="setIndex"
                         class="set-row"
                         :class="{ 'set-row-done': set.done }">
                        <span class="col-num set-num">{{ setIndex + 1 }}</span>

                        <span class="col-prev set-prev">
                            {{
 setIndex > 0 && ex.sets[setIndex - 1]?.done
                                ? `${ex.sets[setIndex-1].weight}kg × ${ex.sets[setIndex-1].reps}`
                                : '—'
                            }}
                        </span>

                        <span class="col-kg">
                            <input v-model.number="set.weight"
                                   type="number" step="0.5" min="0"
                                   class="set-input"
                                   :disabled="set.done" />
                        </span>

                        <span class="col-reps">
                            <input v-model.number="set.reps"
                                   type="number" min="1"
                                   class="set-input"
                                   :disabled="set.done" />
                        </span>

                        <span class="col-action">
                            <button v-if="!set.done"
                                    @click="completeSet(exIndex, setIndex)"
                                    class="btn-check"
                                    title="Pabeigt setu">
                                ✓
                            </button>
                            <button v-else
                                    @click="undoSet(exIndex, setIndex)"
                                    class="btn-check btn-check-done"
                                    title="Atcelt">
                                ✓
                            </button>
                            <button @click="removeSet(exIndex, setIndex)"
                                    class="btn-del-set"
                                    title="Dzēst setu">
                                ✕
                            </button>
                        </span>
                    </div>

                    <!-- Pievienot setu -->
                    <button @click="addSet(exIndex)" class="btn-add-set">
                        + Pievienot setu
                    </button>
                </div>

                <!-- Pievienot vingrinājumu -->
                <button @click="openSearch" class="btn-add-exercise">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Pievienot vingrinājumu
                </button>

                <div style="height: 1rem;"></div>
            </div>
            <!-- Statusa josla -->
            <div class="statusbar">
                <div class="status-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    {{ exercises.length }} vingrinājumi
                </div>
                <div class="status-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    {{ doneSets }}/{{ totalSets }} seti
                </div>
                <div class="status-item status-timer">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ formattedTime }}
                </div>
                <button @click="completeWorkout" class="statusbar-finish-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Pabeigt treniņu
                </button>
            </div>

        </div>

        <!-- Meklēšanas panelis -->
        <Teleport to="body">
            <div v-if="searchOpen" class="overlay" @click.self="closeSearch">
                <div class="search-panel">
                    <div class="search-panel-header">
                        <h2 class="search-panel-title">Pievienot vingrinājumu</h2>
                        <button @click="closeSearch" class="btn-close-search">✕</button>
                    </div>

                    <input v-model="searchQuery"
                           class="search-input"
                           placeholder="Meklēt vingrinājumu..."
                           autofocus />

                    <div class="filter-chips">
                        <button v-for="g in muscleGroups" :key="g"
                                @click="toggleMuscleGroup(g)"
                                class="filter-chip"
                                :class="{ 'filter-chip-active': selectedMuscleGroups.includes(g) }">
                            {{ g }}
                        </button>
                    </div>

                    <div class="search-results">
                        <div v-for="ex in filteredExercises" :key="ex.id"
                             class="search-item"
                             @click="addExercise(ex)">
                            <div class="search-item-left">
                                <div class="search-item-name">{{ ex.name }}</div>
                                <div class="search-item-muscle">{{ ex.muscle_group }}</div>
                            </div>
                            <span class="search-item-add">+ Pievienot</span>
                        </div>
                        <div v-if="!filteredExercises.length" class="search-empty">
                            Nav atrasts neviens vingrinājums
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <Modal ref="modalRef" />
    </AppLayout>
</template>

<style scoped>
    /* ===== LAPA ===== */
    .page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 1rem 5rem;
        min-height: 100vh;
        background-color: #f3f4f6;
    }

    /* ===== TOPBAR ===== */
    .topbar {
        position: sticky;
        top: 0;
        z-index: 20;
        background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        gap: 1rem;
        margin: 0 -1rem;
        box-shadow: 0 2px 12px rgba(230, 92, 0, 0.3);
        flex-wrap: wrap;
    }

    .topbar-left {
        flex: 1;
        min-width: 0;
    }

    .topbar-center {
        flex-shrink: 0;
    }

    .topbar-right {
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .workout-title-input {
        width: 100%;
        border: none;
        outline: none;
        font-size: 1.125rem;
        font-weight: 700;
        color: white;
        background: transparent;
        display: block;
    }

        .workout-title-input::placeholder {
            color: rgba(255,255,255,0.55);
        }

    .topbar-date {
        font-size: 0.72rem;
        color: rgba(255,255,255,0.75);
        margin-top: 0.15rem;
        display: block;
        text-transform: capitalize;
    }

    /* Hronometrs */
    .timer-wrap {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(0,0,0,0.18);
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
    }

    .timer-display {
        font-family: monospace;
        font-size: 1.125rem;
        font-weight: 700;
        color: white;
        min-width: 3.5rem;
        text-align: center;
    }

    .timer-toggle {
        background: rgba(255,255,255,0.2);
        border: none;
        border-radius: 0.25rem;
        width: 1.75rem;
        height: 1.75rem;
        color: white;
        font-size: 0.7rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }

        .timer-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .timer-toggle.timer-paused {
            background: rgba(0,0,0,0.2);
        }

    /* Topbar pogas */
    .btn-cancel {
        padding: 0.4rem 0.875rem;
        border: 2px solid rgba(255,255,255,0.45);
        border-radius: 0.5rem;
        background: transparent;
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
    }

        .btn-cancel:hover {
            background: rgba(255,255,255,0.15);
            border-color: white;
        }

    .btn-finish {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.875rem;
        border: none;
        border-radius: 0.5rem;
        background: #111827;
        color: white;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
    }

        .btn-finish:hover {
            background: #1f2937;
        }

    /* ===== PROGRESA JOSLA ===== */
    .progress-wrap {
        height: 4px;
        background: #e5e7eb;
        margin: 0 -1rem;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(to right, #ff8c42, #e65c00);
        transition: width 0.4s ease;
    }

    /* ===== VINGRINĀJUMU SARAKSTS ===== */
    .exercises-list {
        padding-top: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* ===== VINGRINĀJUMA KARTE ===== */
    .exercise-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .exercise-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem 0.75rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .exercise-header-info {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .exercise-name {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }

    .exercise-muscle-badge {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #e65c00;
        background: #fff7ed;
        padding: 0.15rem 0.55rem;
        border-radius: 9999px;
        border: 1px solid #fed7aa;
    }

    .btn-remove-ex {
        background: none;
        border: none;
        color: #d1d5db;
        font-size: 0.875rem;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        transition: all 0.15s;
        flex-shrink: 0;
    }

        .btn-remove-ex:hover {
            color: #ef4444;
            background: #fef2f2;
        }

    /* ===== SETU GALVENE ===== */
    .sets-header {
        display: grid;
        grid-template-columns: 2rem 1fr 4.5rem 4.5rem 4rem;
        gap: 0.25rem;
        padding: 0.4rem 1.25rem;
        background: #f9fafb;
        border-bottom: 1px solid #f3f4f6;
    }

        .sets-header span {
            font-size: 0.65rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: center;
        }

        .sets-header .col-prev {
            text-align: left;
        }

    /* ===== SETU RINDA ===== */
    .set-row {
        display: grid;
        grid-template-columns: 2rem 1fr 4.5rem 4.5rem 4rem;
        gap: 0.25rem;
        align-items: center;
        padding: 0.45rem 1.25rem;
        border-bottom: 1px solid #f9fafb;
        transition: background 0.15s;
    }

        .set-row:last-of-type {
            border-bottom: none;
        }

    .set-row-done {
        background: #fff7ed;
    }

    .col-num {
        text-align: center;
    }

    .col-prev {
        text-align: left;
    }

    .col-kg {
        text-align: center;
    }

    .col-reps {
        text-align: center;
    }

    .col-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
    }

    .set-num {
        font-size: 0.8rem;
        font-weight: 700;
        color: #9ca3af;
    }

    .set-prev {
        font-size: 0.72rem;
        color: #9ca3af;
    }

    .set-input {
        width: 100%;
        text-align: center;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        padding: 0.35rem 0.2rem;
        font-size: 0.9rem;
        color: #111827;
        font-weight: 500;
        background: white;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

        .set-input:focus {
            border-color: #ff8c42;
            box-shadow: 0 0 0 2px rgba(255,140,66,0.15);
        }

        .set-input:disabled {
            background: transparent;
            border-color: transparent;
            color: #374151;
        }

    /* Atzīmēšanas poga */
    .btn-check {
        width: 1.875rem;
        height: 1.875rem;
        border-radius: 0.375rem;
        border: 2px solid #d1d5db;
        background: white;
        color: #9ca3af;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }

        .btn-check:hover {
            border-color: #ff8c42;
            color: #ff8c42;
        }

    .btn-check-done {
        background: #ff8c42;
        border-color: #ff8c42;
        color: white;
    }

        .btn-check-done:hover {
            background: #e65c00;
            border-color: #e65c00;
        }

    .btn-del-set {
        background: none;
        border: none;
        color: #e5e7eb;
        font-size: 0.7rem;
        cursor: pointer;
        padding: 0.2rem 0.3rem;
        border-radius: 0.25rem;
        transition: color 0.15s;
        flex-shrink: 0;
    }

        .btn-del-set:hover {
            color: #ef4444;
        }

    /* ===== PIEVIENOT SETU ===== */
    .btn-add-set {
        display: block;
        width: 100%;
        padding: 0.65rem;
        background: #f9fafb;
        border: none;
        border-top: 1px solid #f3f4f6;
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        text-align: center;
    }

        .btn-add-set:hover {
            background: #fff7ed;
            color: #e65c00;
            font-weight: 600;
        }

    /* ===== PIEVIENOT VINGRINĀJUMU ===== */
    .btn-add-exercise {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 1rem;
        background: white;
        border: 2px dashed #fed7aa;
        border-radius: 1rem;
        color: #ff8c42;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }

        .btn-add-exercise:hover {
            background: #fff7ed;
            border-color: #ff8c42;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,140,66,0.15);
        }

    /* ===== STATUSA JOSLA ===== */
    .statusbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 3px solid #ff8c42;
        padding: 0.65rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 -2px 12px rgba(0,0,0,0.08);
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 500;
    }

    .status-timer {
        font-family: monospace;
        font-size: 0.9rem;
        font-weight: 700;
        color: #111827;
    }

    /* ===== MEKLĒŠANAS OVERLAY ===== */
    .overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 100;
        display: flex;
        align-items: flex-end;
        justify-content: center;
    }

    .search-panel {
        background: white;
        width: 100%;
        max-width: 700px;
        border-radius: 1.25rem 1.25rem 0 0;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        padding: 1.25rem 1.25rem 0;
    }

    .search-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .search-panel-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #111827;
    }

    .btn-close-search {
        background: #f3f4f6;
        border: none;
        border-radius: 50%;
        width: 2rem;
        height: 2rem;
        cursor: pointer;
        font-size: 0.875rem;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }

        .btn-close-search:hover {
            background: #e5e7eb;
            color: #111827;
        }

    .search-input {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        outline: none;
        margin-bottom: 0.75rem;
        color: #111827;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

        .search-input:focus {
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.1);
        }

    .filter-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.75rem;
    }

    .filter-chip {
        padding: 0.3rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        background: #f9fafb;
        color: #6b7280;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
    }

        .filter-chip:hover:not(.filter-chip-active) {
            background: #f3f4f6;
            color: #374151;
        }

    .filter-chip-active {
        background: #ff8c42;
        border-color: #ff8c42;
        color: white;
    }

    .search-results {
        overflow-y: auto;
        flex: 1;
        padding-bottom: 1.25rem;
    }

    .search-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0.5rem;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        border-radius: 0.5rem;
        transition: background 0.15s;
    }

        .search-item:hover {
            background: #fff7ed;
        }

        .search-item:last-child {
            border-bottom: none;
        }

    .search-item-left {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .search-item-name {
        font-weight: 600;
        color: #111827;
        font-size: 0.9rem;
    }

    .search-item-muscle {
        font-size: 0.7rem;
        color: #6b7280;
    }

    .search-item-add {
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        background: #ff8c42;
        padding: 0.25rem 0.65rem;
        border-radius: 0.375rem;
        flex-shrink: 0;
        transition: background 0.15s;
    }

    .search-item:hover .search-item-add {
        background: #e65c00;
    }

    .search-empty {
        text-align: center;
        padding: 2.5rem 1rem;
        color: #9ca3af;
        font-size: 0.875rem;
    }

    .statusbar-finish-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1.25rem;
        border: none;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }

        .statusbar-finish-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
        }

    /* ===== RESPONSĪVS DIZAINS ===== */


    /* Mobilais */
    @media (max-width: 480px) {
        .page {
            padding: 0 0.5rem 5rem;
        }

        .topbar {
            padding: 0.65rem 0.75rem;
        }

        .topbar-left {
            order: 1;
            flex: 1 1 100%;
        }

        .topbar-center {
            order: 3;
            flex: 1 1 100%;
            justify-content: center;
        }

        .topbar-right {
            order: 2;
            flex-shrink: 0;
        }

        .timer-wrap {
            justify-content: center;
            padding: 0.4rem 0.75rem;
            border-radius: 0.5rem;
        }

        /* Apslēpj "Iepriekš" kolonnu — par maz vietas */
        .col-prev {
            display: none;
        }

        .sets-header {
            grid-template-columns: 1.75rem 3.75rem 3.75rem 3.5rem;
        }

        .set-row {
            grid-template-columns: 1.75rem 3.75rem 3.75rem 3.5rem;
        }

        .set-input {
            font-size: 0.875rem;
            padding: 0.3rem 0.1rem;
        }

        .btn-check {
            width: 1.75rem;
            height: 1.75rem;
            font-size: 0.75rem;
        }

        .exercise-name {
            font-size: 0.9rem;
        }

        .exercise-header {
            padding: 0.75rem 0.875rem 0.6rem;
        }

        .btn-add-set {
            font-size: 0.8rem;
            padding: 0.55rem;
        }

        .btn-add-exercise {
            font-size: 0.85rem;
            padding: 0.875rem;
        }

        .exercises-list {
            padding-top: 0.875rem;
            gap: 0.75rem;
        }

        /* Statusbar mobilā versijā — 2 rindas */
        .statusbar {
            flex-wrap: wrap;
            padding: 0.5rem 0.875rem;
            gap: 0.4rem;
        }

        .statusbar-finish-btn {
            flex: 1 1 100%;
            justify-content: center;
            padding: 0.6rem;
            order: -1;
            border-radius: 0.5rem;
        }

        .status-item {
            font-size: 0.72rem;
        }

        .status-timer {
            font-size: 0.8rem;
        }

        /* Meklēšanas panelis pilna platums mobilā */
        .search-panel {
            padding: 1rem 1rem 0;
            border-radius: 1rem 1rem 0 0;
        }

        .search-panel-title {
            font-size: 1rem;
        }

        .filter-chip {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
        }

        .search-item {
            padding: 0.65rem 0.25rem;
        }

        .search-item-name {
            font-size: 0.85rem;
        }

        .search-item-add {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
        }
    }

    /* Ļoti mazs mobilais */
    @media (max-width: 360px) {
        .sets-header {
            grid-template-columns: 1.5rem 3.5rem 3.5rem 3rem;
            padding: 0.3rem 0.75rem;
        }

        .set-row {
            grid-template-columns: 1.5rem 3.5rem 3.5rem 3rem;
            padding: 0.35rem 0.75rem;
        }

        .btn-check {
            width: 1.5rem;
            height: 1.5rem;
        }

        .btn-del-set {
            display: none;
        }
    }
</style>
