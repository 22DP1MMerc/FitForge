<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import axios from 'axios';

const page = usePage();
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
            rest_seconds: e.pivot?.rest_seconds || e.rest_seconds || 60,
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
</script>

<template>
    <Head title="Panelis" />
    <AppLayout>
        <div class="page">
            <div class="dashboard">

                <!-- Galvene -->
                <div class="topbar">
                    <div class="topbar-left">
                        <h1 class="topbar-title">Sveiks, {{ user.name }}! 👋</h1>
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
            </div>
        </div>
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
        padding: 0.75rem 1rem;
        margin-bottom: 0.875rem;
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
        gap: 0.4rem;
    }

    .schedule-day {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.6rem 0.75rem;
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
        margin-top: 0.875rem;
        padding: 0.875rem;
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        border: 1px solid #fed7aa;
        border-radius: 0.625rem;
    }

    .motivation-row {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
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

    @media (max-width: 480px) {
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
</style>
