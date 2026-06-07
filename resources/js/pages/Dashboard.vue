<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { CheckCircle, Target, Plus, Trash2, Edit, ShieldAlert, Dumbbell, Zap, Medal, Calendar, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import type { SharedData } from '@/types';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth?.user || { name: 'Sportists' });

const props = withDefaults(defineProps<{
    stats?: any;
    todayWorkout?: any;
    recentActivities?: any[];
    weeklySchedule?: any[];
    weeklyWeightStats?: any;
    activeRoutine?: any;
    hasActiveWorkout?: boolean;
}>(), {
    stats: () => ({
        currentStreak: 0, weeklyWorkouts: 0, totalWorkouts: 0,
        totalDuration: 0, goalsAchieved: 0, personalRecords: 0,
        weeklyProgress: {}
    }),
    todayWorkout:      () => null,
    recentActivities:  () => [],
    weeklySchedule:    () => [],
    weeklyWeightStats: () => ({}),
    activeRoutine:     () => null,
    hasActiveWorkout:  false,
});

const activeRoutine = computed(() => props.activeRoutine);

const currentDate = computed(() => new Date().toLocaleDateString('lv-LV', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
}));

const todayIndex = computed(() => {
    const d = new Date().getDay();
    return d === 0 ? 6 : d - 1;
});

const weekDaysShort = ['P', 'O', 'T', 'C', 'Pk', 'S', 'Sv'];
const weekDaysFull  = ['Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena', 'Svētdiena'];

const getTodayExercises = () => {
    if (!activeRoutine.value?.exercises) return [];
    const d = new Date().getDay();
    const today = d === 0 ? 7 : d;
    return activeRoutine.value.exercises
        .filter((e: any) => (e.pivot?.day_number || e.day_number || 1) === today)
        .map((e: any) => ({
            id: e.id, name: e.name, muscle_group: e.muscle_group || '',
            sets: e.pivot?.sets || e.sets || 3, reps: e.pivot?.reps || e.reps || 10,
            day_number: e.pivot?.day_number || e.day_number || 1,
        }));
};

const getExercisesCountForDay = (dayNumber: number) => {
    if (!activeRoutine.value?.exercises) return 0;
    return activeRoutine.value.exercises
        .filter((e: any) => (e.day_number || e.pivot?.day_number || 1) === dayNumber).length;
};

const isTodayWithExercises = (index: number) =>
    index === todayIndex.value && getTodayExercises().length > 0;

const getActiveRoutineExerciseCount = () => {
    if (!activeRoutine.value) return 0;
    if (Array.isArray(activeRoutine.value.exercises)) return activeRoutine.value.exercises.length;
    return activeRoutine.value.exercises_count ?? 0;
};

const getRecommendedWorkouts = () => {
    if (!activeRoutine.value?.exercises?.length) return 3;
    const uniqueDays = new Set<number>();
    activeRoutine.value.exercises.forEach((e: any) => {
        const day = e.day_number ?? e.pivot?.day_number;
        if (day != null) uniqueDays.add(day);
    });
    return uniqueDays.size > 0 ? Math.min(uniqueDays.size, 7) : 3;
};

const weeklyScheduleData = computed(() => {
    const schedule = Array.isArray(props.weeklySchedule) ? props.weeklySchedule : [];
    if (schedule.length === 0) {
        return weekDaysFull.map((dayName, i) => ({
            day_name: dayName, workout_name: 'Atpūtas diena',
            routine_id: null, is_active_routine: false,
            day_number: i + 1, has_workout: false, is_rest_day: true,
        }));
    }
    return schedule.map(day => ({
        day_name: day.day_name || weekDaysFull[day.day_number - 1] || '',
        workout_name: day.workout_name || 'Atpūtas diena',
        routine_id: day.routine_id || null,
        is_active_routine: day.is_active_routine || false,
        day_number: day.day_number || 1,
        has_workout: day.has_workout || false,
        is_rest_day: day.is_rest_day !== false,
    }));
});

const getWeekWeightData = () => {
    const ws = props.weeklyWeightStats || {};
    return weekDaysFull.map(d => ws[d]?.totalWeight || 0);
};

const getWeekProgressData = () => {
    const values = getWeekWeightData();
    const max = Math.max(...values);
    if (max === 0) return values.map(() => 0);
    return values.map(v => Math.round(Math.min(Math.max((v / max) * 100, 5), 100)));
};

const hasWeeklyWeightData = () => getWeekWeightData().some(v => v > 0);

const getDayWeight = (index: number) => {
    const val = getWeekWeightData()[index];
    if (!val) return '';
    return val < 1000 ? `${Math.round(val)}kg` : `${(val / 1000).toFixed(1)}t`;
};

const getWeeklyTotal = () => {
    const total = getWeekWeightData().reduce((s, v) => s + v, 0);
    if (!total) return '0 kg';
    return total < 1000 ? `${Math.round(total)} kg` : `${(total / 1000).toFixed(1)} t`;
};

const getWeeklyAverage = () => {
    const values = getWeekWeightData();
    const withWeight = values.filter(v => v > 0);
    if (!withWeight.length) return '0 kg/dienā';
    const avg = Math.round(values.reduce((s, v) => s + v, 0) / withWeight.length);
    return `${avg} kg/dienā`;
};

const getBestDay = () => {
    const values = getWeekWeightData();
    let bestIdx = 0, bestVal = 0;
    values.forEach((v, i) => { if (v > bestVal) { bestVal = v; bestIdx = i; } });
    if (!bestVal) return 'Nav datu';
    return `${weekDaysFull[bestIdx]}: ${Math.round(bestVal)} kg`;
};

const formatDuration = (min: number) => {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const weeklyProgressPct = () => {
    const rec = getRecommendedWorkouts();
    return rec === 0 ? 0 : Math.min(100, Math.round((props.stats.weeklyWorkouts / rec) * 100));
};

const streakMilestone = () => {
    const s = props.stats.currentStreak;
    if (s >= 12) return '1 gads! 🌟';
    if (s >= 8)  return '2 mēneši! 💎';
    if (s >= 4)  return '1 mēnesis! 🥇';
    if (s >= 2)  return '2 nedēļas! 🎯';
    return 'Sākums! 🚀';
};

const motivationMsg = () => {
    const s = props.stats.currentStreak;
    const p = weeklyProgressPct();
    if (s >= 8) return 'Tu esi īsts profesionālis! Turpini tā! 💪';
    if (s >= 4) return 'Lieliski! Tu esi uz pareizā ceļa! 🌟';
    if (s >= 2) return 'Divi nedēļu sērija! Tu esi lielisks! 🎯';
    if (s === 1) return p >= 100 ? 'Pirmā nedēļa pabeigta! Nākamā! 🔥' : 'Pirmā nedēļa! Pabeidzi to! 💪';
    if (p >= 50) return 'Pusceļā! Pabeidzi nedēļu, lai iegūtu streak! ⭐';
    return 'Sāc nedēļu spēcīgi! Katrs treniņš ir solis uz priekšu! 💥';
};

const startFreeWorkout = async () => {
    try {
        const r = await axios.post('/workout/free/start', {
            name: 'Brīvais treniņš - ' + new Date().toLocaleDateString('lv-LV')
        });
        if (r.data?.success) {
            router.visit(r.data.session_id ? `/workout/free?session=${r.data.session_id}` : '/workout/free');
        }
    } catch (e: any) {
        alert('Kļūda sākot treniņu: ' + (e.response?.data?.message || e.message));
    }
};

const startActiveRoutine = async () => {
    if (!activeRoutine.value?.id) { alert('Nav aktīvas rutīnas'); return; }
    const exercises = getTodayExercises();
    if (!exercises.length) {
        const d = new Date().getDay();
        const names = ['Svētdiena','Pirmdiena','Otrdiena','Trešdiena','Ceturtdiena','Piektdiena','Sestdiena'];
        alert(`Šodien (${names[d]}) nav vingrinājumu rutīnā "${activeRoutine.value.name}".`);
        return;
    }
    try {
        const r = await axios.post('/workout/free/start', {
            name: activeRoutine.value.name + ' - ' + new Date().toLocaleDateString('lv-LV'),
            routine_id: activeRoutine.value.id,
            exercises,
        });
        if (r.data?.success) {
            router.visit(r.data.session_id ? `/workout/free?session=${r.data.session_id}` : '/workout/free');
        }
    } catch (e: any) {
        alert('Kļūda sākot treniņu: ' + (e.response?.data?.message || e.message));
    }
};

const clearActiveRoutine = async () => {
    try {
        await axios.post('/api/routines/clear-active');
        router.reload();
    } catch (e) {
        alert('Kļūda notīrot rutīnu');
    }
};

const browseRoutines = () => router.visit('/routines');
const navigateTo    = (path: string) => router.visit(path);
const editSchedule  = () => router.visit(activeRoutine.value ? '/routines' : '/routines/create');

// ── Goals ────────────────────────────────────────────────

interface GoalExercise { id: number; name: string; muscle_group: string; }

interface Goal {
    id: number; user_id: number; title: string; description: string | null;
    type: 'workout' | 'strength' | 'endurance';
    exercise_id: number | null; exercise: GoalExercise | null;
    target_value: number; current_value: number; unit: string | null;
    deadline: string | null; completed: boolean;
    created_at: string; updated_at: string;
}

interface GoalFormData {
    title: string; description: string;
    type: 'workout' | 'strength' | 'endurance';
    exercise_id: string; target_value: string; unit: string; deadline: string;
}

const goals = ref<Goal[]>([]);
const goalsLoading = ref(false);
const showGoalForm = ref(false);
const processingGoalId = ref<number | null>(null);
const goalsError = ref<string | null>(null);
const editingGoal = ref<Goal | null>(null);
const goalFormError = ref('');
const savingGoal = ref(false);
const deleteConfirmId = ref<number | null>(null);
const goalNotification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

const showGoalNotification = (message: string, type: 'success' | 'error' = 'success') => {
    goalNotification.value = { message, type };
    setTimeout(() => { goalNotification.value = null; }, 3500);
};

const goalForm = ref<GoalFormData>({
    title: '', description: '', type: 'workout',
    exercise_id: '', target_value: '', unit: 'treniņi', deadline: '',
});

const strengthExercises = ref<GoalExercise[]>([]);

const loadStrengthExercises = async () => {
    if (strengthExercises.value.length) return;
    try {
        const res = await axios.get('/api/exercises/strength');
        strengthExercises.value = res.data;
    } catch {}
};

const groupedStrengthExercises = computed(() => {
    const map = new Map<string, GoalExercise[]>();
    for (const ex of strengthExercises.value) {
        const g = ex.muscle_group || 'Citi';
        if (!map.has(g)) map.set(g, []);
        map.get(g)!.push(ex);
    }
    return Array.from(map.entries()).map(([label, exercises]) => ({ label, exercises }));
});

watch(() => goalForm.value.type, (type) => {
    const defaults: Record<string, string> = { workout: 'treniņi', strength: 'kg', endurance: 'min' };
    goalForm.value.unit = defaults[type] ?? '';
    if (type !== 'strength') goalForm.value.exercise_id = '';
    if (type === 'strength') loadStrengthExercises();
});

const totalGoals     = computed(() => goals.value.length);
const completedGoals = computed(() => goals.value.filter(g => g.completed).length);
const completionRate = computed(() => totalGoals.value > 0 ? Math.round((completedGoals.value / totalGoals.value) * 100) : 0);
const inProgressGoals = computed(() => goals.value.filter(g => !g.completed));

const loadGoals = async () => {
    try {
        goalsLoading.value = true;
        goalsError.value = null;
        const res = await axios.get('/api/goals');
        goals.value = res.data;
    } catch {
        goalsError.value = 'Neizdevās ielādēt mērķus.';
    } finally {
        goalsLoading.value = false;
    }
};

const saveGoal = async () => {
    goalFormError.value = '';
    if (!goalForm.value.title.trim()) { goalFormError.value = 'Lūdzu, ievadiet mērķa nosaukumu'; return; }
    if (goalForm.value.type === 'strength' && !goalForm.value.exercise_id) { goalFormError.value = 'Spēka mērķim jāizvēlas vingrinājums'; return; }
    if (!goalForm.value.target_value || parseFloat(goalForm.value.target_value) <= 0) { goalFormError.value = 'Lūdzu, ievadiet derīgu mērķa vērtību'; return; }
    try {
        savingGoal.value = true;
        const isEditing = !!editingGoal.value;
        const payload: Record<string, any> = {
            title: goalForm.value.title.trim(),
            description: goalForm.value.description.trim() || null,
            type: goalForm.value.type,
            exercise_id: goalForm.value.exercise_id ? parseInt(goalForm.value.exercise_id) : null,
            target_value: parseFloat(goalForm.value.target_value),
            unit: goalForm.value.unit.trim() || null,
            deadline: goalForm.value.deadline || null,
        };
        if (editingGoal.value) {
            await axios.put(`/api/goals/${editingGoal.value.id}`, payload);
        } else {
            await axios.post('/api/goals', payload);
        }
        await loadGoals();
        showGoalForm.value = false;
        resetGoalForm();
        showGoalNotification(isEditing ? 'Mērķis veiksmīgi atjaunināts!' : 'Mērķis veiksmīgi izveidots!');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            goalFormError.value = (Object.values(error.response.data.errors) as string[][]).flat().join(', ');
        } else {
            goalFormError.value = 'Neizdevās saglabāt mērķi.';
        }
    } finally {
        savingGoal.value = false;
    }
};

const editGoal = (goal: Goal) => {
    editingGoal.value = goal;
    goalForm.value = {
        title: goal.title, description: goal.description || '',
        type: goal.type, exercise_id: goal.exercise_id?.toString() || '',
        target_value: goal.target_value.toString(), unit: goal.unit || '', deadline: goal.deadline || '',
    };
    showGoalForm.value = true;
};

const deleteGoal = async (goalId: number) => {
    try {
        processingGoalId.value = goalId;
        deleteConfirmId.value = null;
        await axios.delete(`/api/goals/${goalId}`);
        await loadGoals();
        showGoalNotification('Mērķis veiksmīgi dzēsts!');
    } catch {
        showGoalNotification('Neizdevās dzēst mērķi', 'error');
    } finally {
        processingGoalId.value = null;
    }
};

const resetGoalForm = () => {
    editingGoal.value = null;
    goalForm.value = { title: '', description: '', type: 'workout', exercise_id: '', target_value: '', unit: 'treniņi', deadline: '' };
};

const cancelGoalForm = () => {
    showGoalForm.value = false;
    goalFormError.value = '';
    resetGoalForm();
};

const goalTypes = [
    { value: 'workout',   label: 'Treniņš',  emoji: '💪' },
    { value: 'strength',  label: 'Spēks',    emoji: '🏋️' },
    { value: 'endurance', label: 'Izturība', emoji: '🏃' },
] as const;

const getGoalTypeConfig = (type: string) => {
    const configs = {
        workout:   { name: 'Treniņš',  color: '#3b82f6', bg: '#eff6ff' },
        strength:  { name: 'Spēks',    color: '#f97316', bg: '#fff7ed' },
        endurance: { name: 'Izturība', color: '#8b5cf6', bg: '#f5f3ff' },
    };
    return configs[type as keyof typeof configs] || configs.workout;
};

const getProgressPct = (goal: Goal) => {
    if (goal.completed) return 100;
    return Math.min(Math.round((goal.current_value / goal.target_value) * 100), 100);
};

onMounted(() => {
    loadGoals();
});
</script>

<template>
    <Head title="Panelis" />
    <AppLayout>
        <div class="page">
            <div class="dashboard">

                <!-- Galvene -->
                <div class="topbar">
                    <div class="topbar-left">
                        <h1 class="topbar-title">Sveiki, {{ user.name }}! 👋</h1>
                        <p class="topbar-sub" v-if="props.stats.currentStreak > 0">
                            <strong>{{ props.stats.currentStreak }}</strong>
                            {{ props.stats.currentStreak === 1 ? 'nedēļas' : 'nedēļu' }} sērija! 🚀
                        </p>
                        <p class="topbar-sub" v-else>Sāc savu fitnesa ceļojumu šonedēļ!</p>
                    </div>
                    <div class="topbar-date">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ currentDate }}
                    </div>
                </div>

                <!-- Aktīvā rutīna banner -->
                <div v-if="activeRoutine" class="routine-banner">
                    <div class="routine-banner-left">
                        <div class="routine-banner-label">🏋️ Aktīvā rutīna</div>
                        <div class="routine-banner-name">{{ activeRoutine.name }}</div>
                        <div class="routine-banner-meta">
                            <span>{{ getActiveRoutineExerciseCount() }} vingrinājumi</span>
                            <span>·</span>
                            <span>{{ getRecommendedWorkouts() }} treniņi/nedēļā</span>
                        </div>
                    </div>
                    <div class="routine-banner-actions">
                        <button @click="startActiveRoutine" class="btn-start-routine">Sākt treniņu</button>
                        <button @click="clearActiveRoutine" class="btn-change-routine">Mainīt rutīnu</button>
                    </div>
                </div>

                <!-- Ātri sākt -->
                <div class="quick-start">
                    <div class="quick-start-header">🚀 Ātri sākt treniņu</div>
                    <div class="quick-start-btns">
                        <button @click="startFreeWorkout" class="qs-btn qs-free">Brīvais treniņš</button>
                        <button @click="startActiveRoutine"
                                class="qs-btn"
                                :class="activeRoutine ? 'qs-routine' : 'qs-disabled'"
                                :disabled="!activeRoutine">
                            {{ activeRoutine ? 'Aktīvā rutīna' : 'Nav rutīnas' }}
                        </button>
                        <button @click="browseRoutines" class="qs-btn qs-browse">Pārlūkot rutīnas</button>
                    </div>
                </div>

                <!-- Statistikas kartiņas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-num">{{ props.stats.weeklyWorkouts }}/{{ getRecommendedWorkouts() }}</div>
                            <div class="stat-lbl">Šī nedēļa</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                            </svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-num">{{ props.stats.totalWorkouts }}</div>
                            <div class="stat-lbl">Kopā treniņu</div>
                        </div>
                    </div>

                    <div class="stat-card stat-streak">
                        <div class="stat-icon-wrap streak-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-num">{{ props.stats.currentStreak }} {{ props.stats.currentStreak === 1 ? 'nedēļa' : 'nedēļas' }}</div>
                            <div class="stat-lbl">Sērija</div>
                            <div v-if="props.stats.currentStreak > 0" class="streak-milestone">
                                🔥 {{ streakMilestone() }}
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrap">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-body">
                            <div class="stat-num">{{ formatDuration(props.stats.totalDuration) }}</div>
                            <div class="stat-lbl">Kopējais laiks</div>
                        </div>
                    </div>
                </div>

                <!-- Galvenais saturs -->
                <div class="content-grid">
                    <div class="left-col">

                        <!-- Šodienas treniņš -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Šodienas treniņš</h2>
                                <span v-if="activeRoutine" class="badge-orange">Aktīvā rutīna</span>
                            </div>
                            <div class="card-body">
                                <div v-if="activeRoutine">
                                    <div class="today-routine-name">{{ activeRoutine.name }}</div>
                                    <p v-if="activeRoutine.description" class="today-routine-desc">{{ activeRoutine.description }}</p>
                                    <div v-if="getTodayExercises().length > 0" class="today-exercises">
                                        <div v-for="ex in getTodayExercises().slice(0, 3)" :key="ex.id" class="today-ex-row">
                                            <span class="today-ex-icon">💪</span>
                                            <div>
                                                <div class="today-ex-name">{{ ex.name }}</div>
                                                <div class="today-ex-sub">{{ ex.sets }}×{{ ex.reps }}</div>
                                            </div>
                                        </div>
                                        <div v-if="getTodayExercises().length > 3" class="today-ex-more">
                                            + vēl {{ getTodayExercises().length - 3 }} vingrinājumi
                                        </div>
                                    </div>
                                    <button @click="startActiveRoutine" class="btn-start-today">Sākt rutīnu</button>
                                </div>
                                <div v-else class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="40" height="40" style="color:#d1d5db;margin-bottom:0.75rem">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <p class="empty-title">Nav aktīvas rutīnas</p>
                                    <p class="empty-desc">Izvēlies rutīnu vai sāc brīvo treniņu</p>
                                    <button @click="browseRoutines" class="btn-start-today">Izvēlēties rutīnu</button>
                                    <button @click="startFreeWorkout" class="btn-free-today">Brīvais treniņš</button>
                                </div>
                            </div>
                        </div>

                        <!-- Nedēļas svara progress -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Nedēļas svara progress</h2>
                                <span class="card-header-value">{{ getWeeklyTotal() }}</span>
                            </div>
                            <div class="card-body">
                                <template v-if="hasWeeklyWeightData()">
                                    <div class="week-chart">
                                        <div v-for="(val, i) in getWeekProgressData()" :key="i"
                                             class="chart-col" :class="{ 'chart-today': i === todayIndex }">
                                            <div class="chart-bar-wrap">
                                                <div class="chart-weight-label" v-if="val > 0">{{ getDayWeight(i) }}</div>
                                                <div class="chart-bar" :style="{ height: val + '%' }"></div>
                                            </div>
                                            <span class="chart-day-label">{{ weekDaysShort[i] }}</span>
                                        </div>
                                    </div>
                                    <div class="chart-stats">
                                        <div class="chart-stat">
                                            <div class="chart-stat-lbl">Vidējais svars</div>
                                            <div class="chart-stat-val">{{ getWeeklyAverage() }}</div>
                                        </div>
                                        <div class="chart-stat">
                                            <div class="chart-stat-lbl">Labākā diena</div>
                                            <div class="chart-stat-val">{{ getBestDay() }}</div>
                                        </div>
                                        <div class="chart-stat">
                                            <div class="chart-stat-lbl">Kopējais svars</div>
                                            <div class="chart-stat-val">{{ getWeeklyTotal() }}</div>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="chart-empty">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><path d="M3 3v18h18"/><path d="M7 16l4-4 4 4 4-6"/></svg>
                                    <span>Nav svara datu šonedēļ</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-col">

                        <!-- Ātrās darbības -->
                        <div class="card">
                            <div class="card-header"><h2>Ātrās darbības</h2></div>
                            <div class="card-body">
                                <div class="actions-grid">
                                    <button @click="navigateTo('/routines/create')" class="action-btn">
                                        <span class="action-emoji">➕</span><span>Izveidot rutīnu</span>
                                    </button>
                                    <button @click="browseRoutines" class="action-btn">
                                        <span class="action-emoji">📋</span><span>Mana rutīna</span>
                                    </button>
                                    <button @click="editSchedule" class="action-btn">
                                        <span class="action-emoji">📅</span>
                                        <span>{{ activeRoutine ? 'Mainīt rutīnu' : 'Iestatīt rutīnu' }}</span>
                                    </button>
                                    <button @click="navigateTo('/exercises')" class="action-btn">
                                        <span class="action-emoji">🏋️</span><span>Vingrinājumi</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Šī nedēļa -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Šī nedēļa</h2>
                                <span v-if="props.stats.currentStreak > 0" class="streak-badge">
                                    🔥 {{ props.stats.currentStreak }}{{ props.stats.currentStreak === 1 ? ' nedēļa' : ' nedēļas' }}
                                </span>
                                <button @click="editSchedule" class="btn-edit-schedule">
                                    {{ activeRoutine ? 'Mainīt' : 'Iestatīt' }}
                                </button>
                            </div>
                            <div class="card-body">
                                <div v-if="activeRoutine" class="routine-week-info">
                                    <div class="routine-week-row">
                                        <span class="routine-week-name">{{ activeRoutine.name }}</span>
                                        <span class="badge-orange">{{ getActiveRoutineExerciseCount() }} vingr.</span>
                                    </div>
                                    <div class="routine-week-meta">
                                        <span>{{ getRecommendedWorkouts() }} treniņi/nedēļā</span>
                                        <span>·</span>
                                        <span>Šodien: {{ getTodayExercises().length }} vingrinājumi</span>
                                    </div>
                                </div>

                                <div class="schedule-list">
                                    <div v-for="(day, i) in weeklyScheduleData" :key="i"
                                         class="schedule-day"
                                         :class="{
                                             'sday-today':          i === todayIndex,
                                             'sday-routine':        day.is_active_routine,
                                             'sday-today-routine':  i === todayIndex && day.is_active_routine
                                         }">
                                        <span class="sday-name">{{ day.day_name }}</span>
                                        <span class="sday-workout">
                                            <span v-if="day.is_active_routine">🏋️ </span>
                                            {{ day.has_workout ? day.workout_name : 'Atpūtas diena' }}
                                        </span>
                                        <span v-if="isTodayWithExercises(i)" class="sday-count">
                                            {{ getExercisesCountForDay(i + 1) }}
                                        </span>
                                        <span v-else-if="!day.has_workout" class="sday-rest">Atpūta</span>
                                    </div>
                                </div>

                                <div v-if="props.stats.currentStreak > 0" class="motivation-box">
                                    <div class="motivation-row">
                                        <span class="motivation-icon">🔥</span>
                                        <span class="motivation-text">{{ motivationMsg() }}</span>
                                    </div>
                                    <div class="week-progress-wrap">
                                        <div class="week-progress-label">
                                            Šonedēļ: {{ props.stats.weeklyWorkouts }}/{{ getRecommendedWorkouts() }} treniņi
                                        </div>
                                        <div class="week-progress-bar">
                                            <div class="week-progress-fill" :style="{ width: weeklyProgressPct() + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mērķi -->
                <div class="goals-section">
                    <div class="card">
                        <div class="card-header">
                            <div class="goals-header-left">
                                <h2>🎯 Mani mērķi</h2>
                                <div v-if="totalGoals > 0" class="goals-meta">
                                    <span class="goals-stat">{{ completedGoals }}/{{ totalGoals }} pabeigti</span>
                                    <div class="goals-progress-bar">
                                        <div class="goals-progress-fill" :style="{ width: completionRate + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                            <button @click="showGoalForm = true; resetGoalForm()" class="btn-new-goal">
                                <Plus :size="15" />
                                Jauns mērķis
                            </button>
                        </div>
                        <div class="card-body">
                            <div v-if="goalsLoading" class="goals-loading">
                                <Loader2 :size="22" class="spin" />
                                <span>Ielādē...</span>
                            </div>
                            <div v-else-if="goalsError" class="goals-error">
                                <ShieldAlert :size="16" />
                                {{ goalsError }}
                            </div>
                            <div v-else-if="goals.length === 0" class="goals-empty">
                                <Target :size="36" />
                                <p>Nav izveidots neviens mērķis</p>
                                <button @click="showGoalForm = true; resetGoalForm()" class="btn-new-goal-empty">
                                    Izveidot pirmo mērķi
                                </button>
                            </div>
                            <div v-else class="goals-grid">
                                <div v-for="goal in goals" :key="goal.id" class="goal-card" :class="{ 'goal-done': goal.completed }">
                                    <div class="goal-card-top">
                                        <span class="goal-type-chip" :style="{ background: getGoalTypeConfig(goal.type).bg, color: getGoalTypeConfig(goal.type).color }">
                                            {{ getGoalTypeConfig(goal.type).name }}
                                        </span>
                                        <div class="goal-card-actions">
                                            <button @click="editGoal(goal)" class="goal-act-btn goal-act-edit"><Edit :size="14" color="#3b82f6" /></button>
                                            <div v-if="deleteConfirmId === goal.id" class="del-confirm">
                                                <span>Dzēst?</span>
                                                <button @click="deleteGoal(goal.id)" class="del-yes">Jā</button>
                                                <button @click="deleteConfirmId = null" class="del-no">Nē</button>
                                            </div>
                                            <button v-else @click="deleteConfirmId = goal.id" class="goal-act-btn goal-act-del"><Trash2 :size="14" color="#ef4444" /></button>
                                        </div>
                                    </div>
                                    <div class="goal-card-title">{{ goal.title }}</div>
                                    <div class="goal-exercise" :class="{ 'goal-exercise--empty': !goal.exercise }">
                                        <template v-if="goal.exercise">
                                            <Dumbbell :size="11" />
                                            {{ goal.exercise.name }}
                                        </template>
                                    </div>
                                    <div class="goal-progress-wrap">
                                        <div class="goal-progress-row">
                                            <span>{{ goal.current_value }} / {{ goal.target_value }} {{ goal.unit }}</span>
                                            <span class="goal-pct">{{ getProgressPct(goal) }}%</span>
                                        </div>
                                        <div class="goal-bar">
                                            <div class="goal-bar-fill" :style="{ width: getProgressPct(goal) + '%', background: getGoalTypeConfig(goal.type).color }"></div>
                                        </div>
                                    </div>
                                    <div class="goal-card-footer">
                                        <span v-if="goal.completed" class="badge-done"><CheckCircle :size="12" /> Sasniegts!</span>
                                        <span v-else class="badge-auto"><Zap :size="11" /> Auto-izsekots</span>
                                        <span v-if="goal.deadline" class="goal-deadline">
                                            <Calendar :size="11" />
                                            {{ new Date(goal.deadline).toLocaleDateString('lv-LV') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Goal notification toast -->
        <Transition name="goal-toast">
            <div v-if="goalNotification" class="goal-toast" :class="goalNotification.type">
                <CheckCircle v-if="goalNotification.type === 'success'" :size="16" />
                <ShieldAlert v-else :size="16" />
                {{ goalNotification.message }}
            </div>
        </Transition>

        <!-- Goal form modal — teleported to body -->
        <Teleport to="body">
            <Transition name="modal-anim">
                <div v-if="showGoalForm" class="gm-overlay" @click.self="cancelGoalForm">
                    <div class="gm-panel">
                        <div class="gm-header">
                            <div class="gm-header-icon" :class="`gm-type-${goalForm.type}`">
                                <Target :size="20" />
                            </div>
                            <div class="gm-header-text">
                                <h3 class="gm-title">{{ editingGoal ? 'Rediģēt mērķi' : 'Jauns mērķis' }}</h3>
                                <p class="gm-subtitle">{{ editingGoal ? 'Atjaunini mērķa informāciju' : 'Izvirzi jaunu fitnesa mērķi' }}</p>
                            </div>
                            <button @click="cancelGoalForm" class="gm-close">
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M13.5 4.5L4.5 13.5M4.5 4.5L13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <div v-if="goalFormError" class="gm-error">
                            <ShieldAlert :size="15" />
                            <span>{{ goalFormError }}</span>
                        </div>
                        <div class="gm-body">
                            <div class="gm-field">
                                <label class="gm-label">Tips</label>
                                <div class="gm-type-pills">
                                    <button type="button" v-for="t in goalTypes" :key="t.value"
                                        @click="goalForm.type = t.value"
                                        class="gm-pill" :class="{ active: goalForm.type === t.value, [`pill-${t.value}`]: true }">
                                        <span>{{ t.emoji }}</span>
                                        <span>{{ t.label }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="gm-field">
                                <label class="gm-label">Nosaukums <span class="gm-required">*</span></label>
                                <input v-model="goalForm.title" class="gm-input" placeholder="Piemēram: Noskriet 5 km" @input="goalFormError = ''" />
                            </div>
                            <div v-if="goalForm.type === 'strength'" class="gm-field">
                                <label class="gm-label">Vingrinājums <span class="gm-required">*</span></label>
                                <select v-model="goalForm.exercise_id" class="gm-input gm-select" @change="goalFormError = ''">
                                    <option value="">— izvēlies vingrinājumu —</option>
                                    <optgroup v-for="group in groupedStrengthExercises" :key="group.label" :label="group.label">
                                        <option v-for="ex in group.exercises" :key="ex.id" :value="ex.id.toString()">{{ ex.name }}</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="gm-auto-info">
                                <Zap :size="14" />
                                <span v-if="goalForm.type === 'workout'">Automātiski atjaunojas pēc katra pabeigta treniņa</span>
                                <span v-else-if="goalForm.type === 'strength'">Atjaunojas kad uzstādīts jauns personīgais rekords</span>
                                <span v-else-if="goalForm.type === 'endurance'">Uzkrājas kopējais kardio laiks no visiem treniņiem</span>
                            </div>
                            <div class="gm-field">
                                <label class="gm-label">Apraksts <span class="gm-optional">— pēc izvēles</span></label>
                                <textarea v-model="goalForm.description" class="gm-textarea" rows="2" placeholder="Īss apraksts par mērķi..."></textarea>
                            </div>
                            <div class="gm-row">
                                <div class="gm-field">
                                    <label class="gm-label">Mērķa vērtība <span class="gm-required">*</span></label>
                                    <input v-model="goalForm.target_value" type="number" min="0.01" step="any" class="gm-input"
                                        :placeholder="goalForm.type === 'workout' ? '50' : goalForm.type === 'endurance' ? '120' : '100'"
                                        @input="goalFormError = ''" />
                                </div>
                                <div class="gm-field">
                                    <label class="gm-label">Mērvienība</label>
                                    <select v-model="goalForm.unit" class="gm-input gm-select">
                                        <template v-if="goalForm.type === 'workout'">
                                            <option value="treniņi">treniņi</option>
                                        </template>
                                        <template v-else-if="goalForm.type === 'strength'">
                                            <option value="kg">kg</option>
                                            <option value="lbs">lbs</option>
                                        </template>
                                        <template v-else-if="goalForm.type === 'endurance'">
                                            <option value="min">min</option>
                                            <option value="km">km</option>
                                            <option value="soļi">soļi</option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div class="gm-field">
                                <label class="gm-label">Termiņš <span class="gm-optional">— pēc izvēles</span></label>
                                <input v-model="goalForm.deadline" type="date" class="gm-input" />
                            </div>
                        </div>
                        <div class="gm-footer">
                            <button @click="cancelGoalForm" class="gm-btn-cancel">Atcelt</button>
                            <button @click="saveGoal" :disabled="savingGoal" class="gm-btn-save">
                                <Loader2 v-if="savingGoal" :size="16" class="spin" />
                                {{ savingGoal ? 'Saglabā...' : (editingGoal ? 'Saglabāt izmaiņas' : 'Izveidot mērķi') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
    .page {
        background: #f3f4f6;
        min-height: 100vh;
    }

    .dashboard {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.5rem 2rem;
    }

    /* TOPBAR */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        margin: 0 -1.5rem 1.5rem;
        background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
        box-shadow: 0 2px 12px rgba(230,92,0,0.3);
    }

    .topbar-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.2rem;
    }

    .topbar-sub {
        font-size: 0.875rem;
        color: rgba(255,255,255,0.85);
    }

        .topbar-sub strong {
            color: white;
        }

    .topbar-date {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(0,0,0,0.18);
        color: white;
        padding: 0.4rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* AKTĪVĀ RUTĪNA BANNER */
    .routine-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: white;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .routine-banner-label {
        font-size: 0.8rem;
        color: #ff8c42;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .routine-banner-name {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .routine-banner-meta {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.65);
        display: flex;
        gap: 0.5rem;
    }

    .routine-banner-actions {
        display: flex;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .btn-start-routine {
        padding: 0.6rem 1.25rem;
        background: #ff8c42;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background 0.15s;
    }

        .btn-start-routine:hover {
            background: #e65c00;
        }

    .btn-change-routine {
        padding: 0.6rem 1.25rem;
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background 0.15s;
    }

        .btn-change-routine:hover {
            background: rgba(255,255,255,0.18);
        }

    /* ĀTRI SĀKT */
    .quick-start {
        background: white;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .quick-start-header {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.875rem;
    }

    .quick-start-btns {
        display: flex;
        gap: 0.875rem;
        flex-wrap: wrap;
    }

    .qs-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.625rem;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.15s;
    }

        .qs-btn:hover:not(.qs-disabled) {
            transform: translateY(-1px);
        }

    .qs-free {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .qs-routine {
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
    }

    .qs-disabled {
        background: #e5e7eb;
        color: #9ca3af;
        cursor: not-allowed;
    }

    .qs-browse {
        background: #111827;
        color: white;
    }

    /* STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.25rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.15s, box-shadow 0.15s;
    }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

    .stat-streak {
        border-top: 3px solid #ff8c42;
    }

    .stat-icon-wrap {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.625rem;
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .streak-icon {
        background: linear-gradient(135deg, #ff6b00, #ff8c00);
    }

    .stat-num {
        font-size: 1.5rem;
        font-weight: 700;
        color: #111827;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-lbl {
        font-size: 0.775rem;
        color: #6b7280;
        font-weight: 500;
    }

    .streak-milestone {
        font-size: 0.72rem;
        color: #ff6b00;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    /* SATURS */
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    .left-col, .right-col {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* KARTIŅAS */
    .card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        gap: 0.5rem;
    }

        .card-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
        }

    .card-header-value {
        font-size: 0.875rem;
        font-weight: 600;
        color: #ff8c42;
    }

    .card-body {
        padding: 1.25rem;
    }

    .badge-orange {
        font-size: 0.7rem;
        font-weight: 600;
        color: #e65c00;
        background: #fff7ed;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        border: 1px solid #fed7aa;
        white-space: nowrap;
    }

    /* ŠODIENAS TRENIŅŠ */
    .today-routine-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.35rem;
    }

    .today-routine-desc {
        font-size: 0.825rem;
        color: #6b7280;
        margin-bottom: 0.875rem;
    }

    .today-exercises {
        margin-bottom: 1rem;
    }

    .today-ex-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 0.5rem;
        margin-bottom: 0.4rem;
    }

    .today-ex-icon {
        font-size: 1.25rem;
    }

    .today-ex-name {
        font-weight: 600;
        color: #111827;
        font-size: 0.875rem;
    }

    .today-ex-sub {
        font-size: 0.775rem;
        color: #6b7280;
    }

    .today-ex-more {
        font-size: 0.8rem;
        color: #9ca3af;
        font-style: italic;
        text-align: center;
        padding: 0.4rem;
        background: #f9fafb;
        border-radius: 0.375rem;
        border: 1px solid #f3f4f6;
    }

    .btn-start-today {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
        border: none;
        border-radius: 0.625rem;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: opacity 0.15s;
        margin-bottom: 0.5rem;
        display: block;
    }

        .btn-start-today:hover {
            opacity: 0.9;
        }

    .btn-free-today {
        width: 100%;
        padding: 0.65rem;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        border-radius: 0.625rem;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background 0.15s;
    }

        .btn-free-today:hover {
            background: #e5e7eb;
        }

    .empty-state {
        text-align: center;
        padding: 1rem 0;
    }

    .empty-title {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.35rem;
    }

    .empty-desc {
        font-size: 0.825rem;
        color: #6b7280;
        margin-bottom: 1rem;
    }

    /* GRAFIKS */
    .chart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.625rem;
        padding: 2rem 1rem;
        color: #9ca3af;
        font-size: 0.8rem;
    }

    .week-chart {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        height: 180px;
        padding: 1rem 0 0;
        margin-bottom: 1.25rem;
        gap: 0.25rem;
    }

    .chart-col {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
    }

    .chart-bar-wrap {
        height: 140px;
        width: 100%;
        max-width: 28px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        position: relative;
    }

    .chart-weight-label {
        position: absolute;
        top: -20px;
        font-size: 0.62rem;
        font-weight: 600;
        color: #ff8c42;
        white-space: nowrap;
        background: white;
        padding: 1px 3px;
        border-radius: 3px;
        border: 1px solid #fed7aa;
    }

    .chart-bar {
        width: 100%;
        background: linear-gradient(180deg, #ff8c42, #e65c00);
        border-radius: 3px 3px 0 0;
        transition: height 0.4s ease;
        opacity: 0.45;
        min-height: 2px;
    }

    .chart-today .chart-bar {
        opacity: 1;
    }

    .chart-day-label {
        font-size: 0.72rem;
        color: #9ca3af;
        font-weight: 500;
    }

    .chart-today .chart-day-label {
        color: #ff8c42;
        font-weight: 700;
    }

    .chart-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        padding-top: 0.875rem;
        border-top: 1px solid #f3f4f6;
    }

    .chart-stat {
        text-align: center;
    }

    .chart-stat-lbl {
        font-size: 0.72rem;
        color: #9ca3af;
        margin-bottom: 0.2rem;
    }

    .chart-stat-val {
        font-size: 0.925rem;
        font-weight: 600;
        color: #111827;
    }

    /* ĀTRĀS DARBĪBAS */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.625rem;
    }

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
        padding: 0.875rem 0.5rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.625rem;
        cursor: pointer;
        transition: all 0.15s;
        font-size: 0.8rem;
        font-weight: 500;
        color: #111827;
    }

        .action-btn:hover {
            background: #fff7ed;
            border-color: #fed7aa;
        }

    .action-emoji {
        font-size: 1.5rem;
    }

    /* ŠĪ NEDĒĻA */
    .streak-badge {
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        margin-left: auto;
        margin-right: 0.5rem;
    }

    .btn-edit-schedule {
        background: #f3f4f6;
        border: none;
        padding: 0.35rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        color: #6b7280;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.15s;
    }

        .btn-edit-schedule:hover {
            background: #e5e7eb;
            color: #374151;
        }

    .routine-week-info {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 0.625rem;
        padding: 0.5rem 0.875rem;
        margin-bottom: 0.625rem;
    }

    .routine-week-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.3rem;
    }

    .routine-week-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #111827;
    }

    .routine-week-meta {
        font-size: 0.775rem;
        color: #6b7280;
        display: flex;
        gap: 0.4rem;
    }

    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .schedule-day {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.35rem 0.75rem;
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        border-radius: 0.5rem;
        transition: background 0.1s;
    }

    .sday-today {
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .sday-routine {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .sday-today-routine {
        background: #fff7ed;
        border-color: #ff8c42;
    }

    .sday-name {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        min-width: 80px;
    }

    .sday-today .sday-name {
        color: #ff8c42;
    }

    .sday-workout {
        flex: 1;
        font-size: 0.8rem;
        color: #6b7280;
    }

    .sday-count {
        font-size: 0.7rem;
        font-weight: 600;
        background: #ff8c42;
        color: white;
        padding: 0.15rem 0.45rem;
        border-radius: 9999px;
    }

    .sday-rest {
        font-size: 0.72rem;
        color: #9ca3af;
        font-style: italic;
    }

    .motivation-box {
        margin-top: 0.625rem;
        padding: 0.625rem 0.875rem;
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        border: 1px solid #fed7aa;
        border-radius: 0.625rem;
    }

    .motivation-row {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .motivation-icon {
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .motivation-text {
        font-size: 0.825rem;
        color: #9a3412;
        font-weight: 500;
        line-height: 1.4;
    }

    .week-progress-wrap {
        background: white;
        padding: 0.6rem 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid #fed7aa;
    }

    .week-progress-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }

    .week-progress-bar {
        height: 6px;
        background: #f3f4f6;
        border-radius: 9999px;
        overflow: hidden;
    }

    .week-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff8c42, #e65c00);
        border-radius: 9999px;
        transition: width 0.4s ease;
    }

    /* RESPONSĪVS */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    /* ── MĒRĶI ─────────────────────────────────────────────── */
    .goals-section { margin-top: 1.25rem; }

    .goals-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
        min-width: 0;
    }

    .goals-meta {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
    }

    .goals-stat {
        font-size: 0.75rem;
        color: #6b7280;
        white-space: nowrap;
    }

    .goals-progress-bar {
        width: 80px;
        height: 5px;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .goals-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff8c42, #e65c00);
        border-radius: 9999px;
        transition: width 0.4s ease;
    }

    .btn-new-goal {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
        transition: opacity 0.15s;
    }

    .btn-new-goal:hover { opacity: 0.9; }

    .goals-loading, .goals-error {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: center;
        padding: 1.5rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .goals-error { color: #dc2626; }

    .goals-empty {
        text-align: center;
        padding: 2rem 1rem;
        color: #9ca3af;
    }

    .goals-empty p { font-size: 0.875rem; margin: 0.5rem 0 1rem; }

    .btn-new-goal-empty {
        padding: 0.5rem 1.25rem;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-new-goal-empty:hover { background: #e5e7eb; }

    .goals-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr));
        gap: 1rem;
    }

    .goal-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.875rem;
        padding: 1rem;
        transition: box-shadow 0.15s;
    }

    .goal-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

    .goal-done { border-color: #bbf7d0; background: #f0fdf4; }

    .goal-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.625rem;
    }

    .goal-type-chip {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
    }

    .goal-card-actions {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .goal-act-btn {
        width: 26px;
        height: 26px;
        padding: 0;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s;
    }

    .goal-act-edit { color: #3b82f6; background: #eff6ff; border: 1px solid #bfdbfe; }
    .goal-act-edit:hover { background: #dbeafe; border-color: #3b82f6; }
    .goal-act-edit :deep(svg) { stroke: #3b82f6; }
    .goal-act-del  { color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; }
    .goal-act-del:hover  { background: #fee2e2; border-color: #ef4444; }
    .goal-act-del :deep(svg) { stroke: #ef4444; }

    .del-confirm {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.72rem;
        color: #6b7280;
    }

    .del-yes, .del-no {
        padding: 0.15rem 0.4rem;
        border: none;
        border-radius: 0.25rem;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
    }

    .del-yes { background: #fef2f2; color: #dc2626; }
    .del-no  { background: #f3f4f6; color: #374151; }

    .goal-card-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .goal-exercise {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.72rem;
        color: #6b7280;
        margin-bottom: 0.5rem;
        min-height: 1.1rem;
    }

    .goal-exercise--empty {
        pointer-events: none;
    }

    .goal-progress-wrap { margin: 0.625rem 0; }

    .goal-progress-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.72rem;
        color: #6b7280;
        margin-bottom: 0.3rem;
    }

    .goal-pct { font-weight: 600; color: #374151; }

    .goal-bar {
        height: 5px;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
    }

    .goal-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }

    .goal-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .badge-done {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #059669;
        background: #ecfdf5;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }

    .badge-auto {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.7rem;
        font-weight: 500;
        color: #6366f1;
        background: #eef2ff;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }

    .goal-deadline {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        font-size: 0.7rem;
        color: #9ca3af;
    }

    /* ── GOAL TOAST ──────────────────────────────────────── */
    .goal-toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .goal-toast.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .goal-toast.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

    .goal-toast-enter-active { transition: all 0.3s ease; }
    .goal-toast-leave-active { transition: all 0.25s ease; }
    .goal-toast-enter-from, .goal-toast-leave-to { opacity: 0; transform: translateY(8px); }

    /* ── GOAL MODAL (gm-*) ───────────────────────────────── */
    .gm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9000;
        padding: 1rem;
    }

    .gm-panel {
        background: #fff;
        border-radius: 1.5rem;
        width: 100%;
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 24px 64px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
    }

    .gm-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem 1.5rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .gm-header-icon {
        width: 46px;
        height: 46px;
        border-radius: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .gm-type-workout   { background: #eff6ff; color: #3b82f6; }
    .gm-type-strength  { background: #fff7ed; color: #f97316; }
    .gm-type-endurance { background: #f5f3ff; color: #8b5cf6; }

    .gm-header-text { flex: 1; }

    .gm-title { font-size: 1.125rem; font-weight: 700; color: #0f172a; margin: 0 0 0.125rem; }
    .gm-subtitle { font-size: 0.8125rem; color: #94a3b8; margin: 0; }

    .gm-close {
        width: 36px;
        height: 36px;
        border-radius: 0.625rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }

    .gm-close:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }

    .gm-error {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 0.75rem 1.5rem;
        font-size: 0.8125rem;
        font-weight: 500;
    }

    .gm-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.125rem;
        overflow-y: auto;
    }

    .gm-field { display: flex; flex-direction: column; gap: 0.375rem; }

    .gm-label { font-size: 0.8rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    .gm-required { color: #ef4444; }
    .gm-optional { color: #94a3b8; font-weight: 400; text-transform: none; letter-spacing: 0; }

    .gm-input {
        width: 100%;
        padding: 0.7rem 0.875rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        color: #0f172a;
        background: #fafafa;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        box-sizing: border-box;
    }

    .gm-input:focus { border-color: #ff8c42; box-shadow: 0 0 0 3px rgba(255,140,66,0.12); background: #fff; }
    .gm-input::placeholder { color: #cbd5e1; }

    .gm-textarea {
        width: 100%;
        padding: 0.7rem 0.875rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        color: #0f172a;
        background: #fafafa;
        resize: vertical;
        min-height: 64px;
        font-family: inherit;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        box-sizing: border-box;
    }

    .gm-textarea:focus { border-color: #ff8c42; box-shadow: 0 0 0 3px rgba(255,140,66,0.12); background: #fff; }
    .gm-textarea::placeholder { color: #cbd5e1; }

    .gm-type-pills { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }

    .gm-pill {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0.625rem 0.25rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.875rem;
        background: #fafafa;
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s;
    }

    .gm-pill:hover { background: #f1f5f9; }
    .gm-pill.active.pill-workout   { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
    .gm-pill.active.pill-strength  { border-color: #f97316; background: #fff7ed; color: #c2410c; }
    .gm-pill.active.pill-endurance { border-color: #8b5cf6; background: #f5f3ff; color: #6d28d9; }

    .gm-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    .gm-auto-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 0.625rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.78rem;
        color: #1d4ed8;
    }

    .gm-select { appearance: none; cursor: pointer; }

    .gm-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
        padding: 1.125rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .gm-btn-cancel {
        padding: 0.625rem 1.25rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: background 0.15s;
    }

    .gm-btn-cancel:hover { background: #f1f5f9; }

    .gm-btn-save {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.5rem;
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        border: none;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }

    .gm-btn-save:hover:not(:disabled) { box-shadow: 0 6px 16px rgba(255,140,66,0.35); transform: translateY(-1px); }
    .gm-btn-save:disabled { opacity: 0.65; cursor: not-allowed; }

    .modal-anim-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
    .modal-anim-leave-active { transition: all 0.2s ease; }
    .modal-anim-enter-from, .modal-anim-leave-to { opacity: 0; }
    .modal-anim-enter-from .gm-panel { transform: scale(0.95) translateY(12px); }
    .modal-anim-leave-to .gm-panel   { transform: scale(0.97) translateY(4px); }

    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 480px) {
        .gm-row { grid-template-columns: 1fr; }
        .goals-grid { grid-template-columns: 1fr; }
        .goals-meta { display: none; }

        .dashboard {
            padding: 0 0.75rem 2rem;
        }

        .topbar {
            padding: 1rem;
        }

        .topbar-date {
            display: none;
        }

        .topbar-title {
            font-size: 1.2rem;
        }

        .routine-banner {
            flex-direction: column;
            align-items: flex-start;
        }

        .routine-banner-actions {
            width: 100%;
        }

        .btn-start-routine, .btn-change-routine {
            flex: 1;
            text-align: center;
        }

        .quick-start-btns {
            flex-direction: column;
        }

        .qs-btn {
            width: 100%;
            text-align: center;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.625rem;
        }

        .stat-num {
            font-size: 1.25rem;
        }

        .streak-badge {
            display: none;
        }
    }

    @media (max-width: 375px) {
        .dashboard {
            padding: 0 0.5rem 2rem;
        }

        .topbar {
            padding: 0.75rem;
        }

        .topbar-title {
            font-size: 1.05rem;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .stat-num {
            font-size: 1.1rem;
        }

        .goals-grid {
            gap: 0.625rem;
        }

        .goal-card {
            padding: 0.75rem;
        }

        .btn-new-goal {
            font-size: 0.8rem;
            padding: 0.45rem 0.875rem;
        }
    }
</style>
