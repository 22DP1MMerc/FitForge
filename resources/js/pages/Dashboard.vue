<template>
    <Head title="Panelis" />

    <AppLayout>
        <div class="dashboard-container">
            <div class="dashboard">
                <!-- Header -->
                <div class="welcome-header">
                    <div class="header-left">
                        <h1>Sveiks, {{ user.name }}! 👋</h1>
                        <p v-if="props.stats.currentStreak > 0">Tev ir {{ props.stats.currentStreak }} dienu sērija!</p>
                        <p v-else>Sāc savu fitnesa ceļojumu šodien!</p>
                    </div>
                    <div class="date-display">
                        <svg class="date-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="date-text">{{ currentDate }}</span>
                    </div>
                </div>

                <!-- Aktīvā rutīnas bannera daļa -->
                <div v-if="activeRoutine" class="active-routine-banner">
                    <div class="active-routine-content">
                        <div class="routine-info">
                            <h2>🏋️ Aktīvā rutīna</h2>
                            <h3>{{ activeRoutine.name }}</h3>
                            <div class="routine-details">
                                <span class="exercise-count">
                                    <svg class="detail-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    {{ activeRoutine.exercises_count || 0 }} vingrinājumi
                                </span>
                            </div>
                        </div>
                        <div class="routine-actions">
                            <button @click="startActiveRoutine" class="start-btn">
                                Sākt treniņu
                            </button>
                            <button @click="clearActiveRoutine" class="clear-btn">
                                Mainīt rutīnu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ātri sākt treniņu sadaļa -->
                <div class="quick-start-actions">
                    <h3>🚀 Ātri sākt treniņu</h3>
                    <div class="start-buttons">
                        <button @click="startFreeWorkout" class="start-btn free">
                            Brīvais treniņš
                        </button>
                        <button @click="startActiveRoutine"
                                :class="['start-btn', activeRoutine ? 'routine' : 'disabled']"
                                :disabled="!activeRoutine">
                            {{ activeRoutine ? 'Aktīvā rutīna' : 'Nav rutīnas' }}
                        </button>
                        <button @click="browseRoutines" class="start-btn browse">
                            Pārlūkot rutīnas
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ props.stats.weeklyWorkouts }}/7 dienas</p>
                            <p class="stat-label">Šī nedēļa</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ props.stats.totalWorkouts }}</p>
                            <p class="stat-label">Kopā treniņu</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ props.stats.currentStreak }} dienas</p>
                            <p class="stat-label">Pašreizējā sērija</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ formatDuration(props.stats.totalDuration) }}</p>
                            <p class="stat-label">Kopējais laiks</p>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="dashboard-content">
                    <!-- Left Column -->
                    <div class="left-column">
                        <!-- Šodienas treniņš -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Šodienas treniņš</h2>
                                <div class="card-header-actions">
                                    <span v-if="activeRoutine" class="active-badge">Aktīvā rutīna</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div v-if="activeRoutine">
                                    <h3 class="workout-name">{{ activeRoutine.name }}</h3>
                                    <p v-if="activeRoutine.description" class="workout-description">
                                        {{ activeRoutine.description }}
                                    </p>
                                    <div class="exercises-preview">
                                        <div class="exercise-preview">
                                            <div class="preview-icon">💪</div>
                                            <div class="preview-text">
                                                <div class="preview-title">Aktīvā rutīna</div>
                                                <div class="preview-subtitle">Izvēlēta kā pamata treniņa programma</div>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="startActiveRoutine"
                                            class="start-workout-btn active">
                                        Sākt rutīnu
                                    </button>
                                </div>
                                <div v-else class="empty-state">
                                    <div class="empty-icon">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                    </div>
                                    <p class="empty-title">Nav aktīvas rutīnas</p>
                                    <p class="empty-description">Izvēlies rutīnu vai sāc brīvo treniņu</p>
                                    <div class="empty-actions">
                                        <button @click="browseRoutines" class="empty-action-btn">
                                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Izvēlēties rutīnu
                                        </button>
                                        <button @click="startFreeWorkout" class="empty-action-btn secondary">
                                            Brīvais treniņš
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nedēļas svara progress -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Nedēļas svara progress</h2>
                                <span class="progress-label">{{ getWeeklyTotalWeight() }}</span>
                            </div>
                            <div class="card-body">
                                <div class="week-chart">
                                    <div v-for="(value, index) in getWeekProgressData()" :key="index"
                                         :class="['chart-day', index === todayIndex ? 'today' : '']">
                                        <div class="chart-bar-container">
                                            <div class="chart-bar" :style="{ height: `${value}%` }"></div>
                                            <div class="chart-weight" v-if="value > 0">
                                                {{ getDayWeight(index) }}
                                            </div>
                                        </div>
                                        <span class="chart-label">{{ weekDaysShort[index] }}</span>
                                    </div>
                                </div>
                                <div class="progress-stats">
                                    <div class="progress-stat">
                                        <span class="stat-label">Vidējais svars</span>
                                        <span class="stat-value">{{ getWeeklyAverageWeight() }}</span>
                                    </div>
                                    <div class="progress-stat">
                                        <span class="stat-label">Labākā diena</span>
                                        <span class="stat-value">{{ getBestDayWeight() }}</span>
                                    </div>
                                    <div class="progress-stat">
                                        <span class="stat-label">Kopējais svars</span>
                                        <span class="stat-value">{{ getWeeklyTotalWeight() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="right-column">
                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Ātrās darbības</h2>
                            </div>
                            <div class="card-body">
                                <div class="actions-grid">
                                    <button @click="navigateTo('/routines/create')" class="action-btn">
                                        <div class="action-icon">➕</div>
                                        <span>Izveidot rutīnu</span>
                                    </button>
                                    <button @click="browseRoutines" class="action-btn">
                                        <div class="action-icon">📋</div>
                                        <span>Mana rutīna</span>
                                    </button>
                                    <button @click="editSchedule" class="action-btn">
                                        <div class="action-icon">📅</div>
                                        <span>{{ activeRoutine ? 'Mainīt rutīnu' : 'Iestatīt rutīnu' }}</span>
                                    </button>
                                    <button @click="navigateTo('/exercises')" class="action-btn">
                                        <div class="action-icon">🏋️</div>
                                        <span>Vingrinājumi</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Šī nedēļa -->
                        <div class="card">
                            <div class="card-header">
                                <h2>Šī nedēļa</h2>
                                <button @click="editSchedule" class="edit-schedule-btn">
                                    {{ activeRoutine ? 'Mainīt rutīnu' : 'Iestatīt rutīnu' }}
                                </button>
                            </div>
                            <div class="card-body">
                                <!-- Rutīnas info, ja ir aktīva -->
                                <div v-if="activeRoutine" class="routine-info-week">
                                    <div class="routine-week-header">
                                        <h3 class="routine-week-title">{{ activeRoutine.name }}</h3>
                                        <span class="routine-exercise-count">
                                            {{ activeRoutine.exercises?.length || activeRoutine.exercises_count || 0 }} vingrinājumi
                                        </span>
                                    </div>

                                    <div v-if="activeRoutine.exercises && activeRoutine.exercises.length > 0"
                                         class="routine-exercises-preview">
                                        <div v-for="(exercise, index) in activeRoutine.exercises.slice(0, 3)"
                                             :key="exercise.id"
                                             class="preview-exercise">
                                            <span class="preview-exercise-name">{{ exercise.name }}</span>
                                            <span class="preview-exercise-sets">{{ exercise.sets || 3 }}x{{ exercise.reps || 10 }}</span>
                                        </div>
                                        <div v-if="activeRoutine.exercises.length > 3" class="more-exercises">
                                            + vēl {{ activeRoutine.exercises.length - 3 }} vingrinājumi
                                        </div>
                                    </div>
                                </div>

                                <div class="schedule-list">
                                    <div v-for="(day, index) in getWeeklyScheduleWithActiveRoutine" :key="index"
                                         :class="['schedule-day', index === todayIndex ? 'today' : '', day.is_active_routine ? 'active-routine-day' : '']">
                                        <span class="day-name">{{ day.day_name }}</span>
                                        <span class="day-workout">
                                            <span v-if="day.is_active_routine" class="routine-indicator">🏋️</span>
                                            {{ day.workout_name }}
                                        </span>
                                        <span v-if="!day.workout_name || day.workout_name === 'Atpūtas diena'"
                                              class="rest-text">Atpūta</span>
                                    </div>
                                </div>
                                <div v-if="activeRoutine" class="schedule-note">
                                    <p>🔄 Visa nedēļa aizpildīta ar aktīvo rutīnu</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head, usePage, router } from '@inertiajs/vue3';
    import { computed, ref, onMounted } from 'vue';

    const page = usePage();

    // Pārbaudām vai dati ir pieejami
    const user = computed(() => page.props.auth?.user || { name: 'Sportists' });

    // Aktīvā rutīna
    const activeRoutine = ref(null);

    // Ielādē aktīvo rutīnu no localStorage
    const loadActiveRoutine = () => {
        const savedRoutine = localStorage.getItem('activeRoutine');
        if (savedRoutine) {
            try {
                activeRoutine.value = JSON.parse(savedRoutine);
            } catch (e) {
                console.error('Error parsing active routine:', e);
                activeRoutine.value = null;
            }
        }
    };

    // Ielādē aktīvo rutīnu, kad komponents ielādējas
    onMounted(() => {
        loadActiveRoutine();

        // Pārbauda izmaiņas rutīnās
        const checkRoutineChanges = () => {
            const routineChanged = localStorage.getItem('routineChanged');
            if (routineChanged === 'true') {
                loadActiveRoutine();
                localStorage.removeItem('routineChanged');
            }
        };

        checkRoutineChanges();

        // Pārbauda katru sekundi
        const interval = setInterval(checkRoutineChanges, 1000);

        // Notīrām intervālu, kad komponents tiek noņemts
        return () => clearInterval(interval);
    });

    // Drošs props iegūšana ar default vērtībām
    const props = withDefaults(defineProps<{
        stats?: object;
        todayWorkout?: object | null;
        recentActivities?: any[];
        weeklySchedule?: any[];
        weeklyWeightStats?: object; // Pievienojam svara statistiku
    }>(), {
        stats: () => ({
            currentStreak: 0,
            weeklyWorkouts: 0,
            totalWorkouts: 0,
            totalDuration: 0,
            caloriesBurned: 0,
            goalsAchieved: 0,
            personalRecords: 0,
            weeklyProgress: {
                monday: 0,
                tuesday: 0,
                wednesday: 0,
                thursday: 0,
                friday: 0,
                saturday: 0,
                sunday: 0
            }
        }),
        todayWorkout: () => null,
        recentActivities: () => [],
        weeklySchedule: () => [],
        weeklyWeightStats: () => ({
            monday: { totalWeight: 0, exercises: 0 },
            tuesday: { totalWeight: 0, exercises: 0 },
            wednesday: { totalWeight: 0, exercises: 0 },
            thursday: { totalWeight: 0, exercises: 0 },
            friday: { totalWeight: 0, exercises: 0 },
            saturday: { totalWeight: 0, exercises: 0 },
            sunday: { totalWeight: 0, exercises: 0 }
        })
    });

    // Pašreizējais datums
    const currentDate = computed(() => {
        return new Date().toLocaleDateString('lv-LV', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    });

    // Šodienas dienas indekss
    const todayIndex = computed(() => {
        const today = new Date().getDay(); // 0 = svētdiena, 1 = pirmdiena, ...
        return today === 0 ? 6 : today - 1; // Pārveido uz 0 = pirmdiena, 6 = svētdiena
    });

    // Nedēļas dienu nosaukumi
    const weekDaysShort = ['P', 'O', 'T', 'C', 'Pk', 'S', 'Sv'];
    const weekDaysFull = ['Pirmdiena', 'Otrdiena', 'Trešdiena', 'Ceturtdiena', 'Piektdiena', 'Sestdiena', 'Svētdiena'];

    // Droša nedēļas grafika iegūšana
    const getWeeklyScheduleWithActiveRoutine = computed(() => {
        const schedule = Array.isArray(props.weeklySchedule) ? [...props.weeklySchedule] : [];

        // Ja ir aktīva rutīna, aizstājam visas dienas ar to
        if (activeRoutine.value) {
            return weekDaysFull.map((dayName, index) => {
                const existingDay = schedule[index] || {};
                return {
                    day_name: dayName,
                    workout_name: activeRoutine.value.name || 'Treniņš',
                    routine_id: activeRoutine.value.id,
                    is_active_routine: true,
                    ...existingDay
                };
            });
        }

        // Ja nav aktīvas rutīnas, izmantojam esošo grafiku
        return weekDaysFull.map((dayName, index) => {
            const existingDay = schedule[index] || {};
            return {
                day_name: dayName,
                workout_name: existingDay.workout_name || 'Atpūtas diena',
                routine_id: existingDay.routine_id || null,
                is_active_routine: false,
                ...existingDay
            };
        });
    });

    // ========== Svaru progresa funkcijas ==========

    // Iegūst svaru datus no props
    const getWeekWeightData = () => {
        const weightStats = props.weeklyWeightStats || {};
        return {
            monday: weightStats.monday?.totalWeight || 0,
            tuesday: weightStats.tuesday?.totalWeight || 0,
            wednesday: weightStats.wednesday?.totalWeight || 0,
            thursday: weightStats.thursday?.totalWeight || 0,
            friday: weightStats.friday?.totalWeight || 0,
            saturday: weightStats.saturday?.totalWeight || 0,
            sunday: weightStats.sunday?.totalWeight || 0
        };
    };

    // Progresa grafikam - normalizē svara datus uz 100%
    const getWeekProgressData = () => {
        const weightData = getWeekWeightData();
        const values = [
            weightData.monday,
            weightData.tuesday,
            weightData.wednesday,
            weightData.thursday,
            weightData.friday,
            weightData.saturday,
            weightData.sunday
        ];

        const maxWeight = Math.max(...values);
        if (maxWeight === 0) return [0, 0, 0, 0, 0, 0, 0];

        return values.map(weight => Math.round((weight / maxWeight) * 100));
    };

    // Atgriež faktisko svaru dienai
    const getDayWeight = (index) => {
        const weightData = getWeekWeightData();
        const weights = [
            weightData.monday,
            weightData.tuesday,
            weightData.wednesday,
            weightData.thursday,
            weightData.friday,
            weightData.saturday,
            weightData.sunday
        ];

        const weight = weights[index];
        if (weight === 0) return '';

        if (weight < 1000) {
            return `${Math.round(weight)}kg`;
        } else {
            return `${(weight / 1000).toFixed(1)}t`;
        }
    };

    // Kopējais nedēļas svars
    const getWeeklyTotalWeight = () => {
        const weightData = getWeekWeightData();
        const total = Object.values(weightData).reduce((sum, weight) => sum + weight, 0);

        if (total === 0) return '0 kg';

        if (total < 1000) {
            return `${Math.round(total)} kg`;
        } else {
            return `${(total / 1000).toFixed(1)} t`;
        }
    };

    // Vidējais svars
    const getWeeklyAverageWeight = () => {
        const weightData = getWeekWeightData();
        const daysWithWeight = Object.values(weightData).filter(weight => weight > 0).length;

        if (daysWithWeight === 0) return '0 kg/dienā';

        const total = Object.values(weightData).reduce((sum, weight) => sum + weight, 0);
        const average = Math.round(total / daysWithWeight);

        return `${average} kg/dienā`;
    };

    // Labākā diena
    const getBestDayWeight = () => {
        const weightData = getWeekWeightData();
        const dayNames = ['Pirmdienā', 'Otrdienā', 'Trešdienā', 'Ceturtdienā', 'Piektdienā', 'Sestdienā', 'Svētdienā'];

        let bestDay = '';
        let bestWeight = 0;

        Object.entries(weightData).forEach(([day, weight], index) => {
            if (weight > bestWeight) {
                bestWeight = weight;
                bestDay = dayNames[index];
            }
        });

        if (bestWeight === 0) return 'Nav datu';

        return `${bestDay}: ${Math.round(bestWeight)} kg`;
    };

    // Funkciju formatēšanai
    const formatDuration = (minutes: number) => {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
    };

    // Navigācijas funkcijas
    const startFreeWorkout = () => {
        router.visit('/workout/free');
    };

    const startActiveRoutine = () => {
        if (activeRoutine.value?.id) {
            const routineData = {
                ...activeRoutine.value,
                exercises: activeRoutine.value.exercises || []
            };
            localStorage.setItem('activeRoutineData', JSON.stringify(routineData));

            router.visit('/workout/free', {
                method: 'get',
                data: {
                    routine_id: activeRoutine.value.id,
                    routine_name: activeRoutine.value.name
                }
            });
        } else {
            router.visit('/workout/free');
        }
    };

    const browseRoutines = () => {
        router.visit('/routines');
    };

    const clearActiveRoutine = () => {
        localStorage.removeItem('activeRoutine');
        activeRoutine.value = null;
        localStorage.setItem('routineChanged', 'true');
    };

    const editSchedule = () => {
        if (activeRoutine.value) {
            router.visit('/routines');
        } else {
            router.visit('/routines/create');
        }
    };

    const navigateTo = (path: string) => {
        router.visit(path);
    };
</script>

<style scoped>
    .dashboard-container {
        min-height: calc(100vh - 4rem);
        background-color: #f9fafb;
    }

    .dashboard {
        padding: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Header */
    .welcome-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
        border-radius: 1rem;
        color: white;
    }

    .header-left h1 {
        font-size: 1.875rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .header-left p {
        opacity: 0.9;
        font-size: 1rem;
    }

    .date-display {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }

    .date-icon {
        width: 1.25rem;
        height: 1.25rem;
    }

    .date-text {
        font-weight: 500;
    }

    /* Aktīvā rutīnas bannera stils */
    .active-routine-banner {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
    }

    .active-routine-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
    }

    .routine-info h2 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .routine-info h3 {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .routine-details {
        display: flex;
        gap: 1.5rem;
        font-size: 0.875rem;
        opacity: 0.9;
        align-items: center;
    }

    .detail-icon {
        width: 1rem;
        height: 1rem;
        margin-right: 0.25rem;
    }

    .routine-actions {
        display: flex;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .start-btn {
        background: white;
        color: #3b82f6;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

        .start-btn:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
        }

    .clear-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

        .clear-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

    /* Ātri sākt treniņu sadaļa */
    .quick-start-actions {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }

        .quick-start-actions h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

    .start-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

        .start-buttons .start-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }

    .start-btn.free {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .start-btn.routine {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .start-btn.disabled {
        background: #9ca3af;
        color: white;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .start-btn.browse {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
    }

    .start-btn:hover:not(.disabled) {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        transition: transform 0.2s, box-shadow 0.2s;
    }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

    .stat-content {
        margin-top: 0.5rem;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .dashboard-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .dashboard-content {
            grid-template-columns: 1fr;
        }

        .active-routine-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .routine-actions {
            width: 100%;
            justify-content: space-between;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .start-buttons {
            flex-direction: column;
        }

            .start-buttons .start-btn {
                width: 100%;
            }

        .routine-actions {
            flex-direction: column;
        }
    }

    /* Kartiņu stili */
    .card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }

    .card-header-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .active-badge {
        background: #3b82f6;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .edit-schedule-btn {
        background: #f3f4f6;
        color: #374151;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

        .edit-schedule-btn:hover {
            background: #e5e7eb;
        }

    .progress-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #10b981;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Šodienas treniņš */
    .workout-name {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .workout-description {
        color: #6b7280;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .exercises-preview {
        margin-bottom: 1.5rem;
    }

    .exercise-preview {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }

    .preview-icon {
        font-size: 1.5rem;
    }

    .preview-text {
        flex: 1;
    }

    .preview-title {
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .preview-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .start-workout-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        background: linear-gradient(135deg, #ff6b00 0%, #ff8c00 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

        .start-workout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }

        .start-workout-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

            .start-workout-btn.active:hover {
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
    }

    .empty-icon {
        margin-bottom: 1rem;
    }

        .empty-icon svg {
            width: 3rem;
            height: 3rem;
            color: #9ca3af;
        }

    .empty-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .empty-description {
        color: #6b7280;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
    }

    .empty-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .empty-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

        .empty-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .empty-action-btn.secondary {
            background: #f3f4f6;
            color: #374151;
        }

            .empty-action-btn.secondary:hover {
                background: #e5e7eb;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

    .btn-icon {
        width: 1rem;
        height: 1rem;
    }

    /* Weekly Progress */
    .week-chart {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        height: 200px;
        padding: 1rem 0;
        margin-bottom: 1.5rem;
    }

    .chart-day {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        gap: 0.5rem;
    }

        .chart-day.today .chart-bar {
            background: linear-gradient(180deg, #ff8c42, #ff6b00);
        }

    .chart-bar-container {
        height: 150px;
        width: 20px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        position: relative;
    }

    .chart-bar {
        width: 20px;
        background: linear-gradient(180deg, #3b82f6, #8b5cf6);
        border-radius: 0.25rem;
        transition: height 0.3s ease;
    }

    .chart-weight {
        position: absolute;
        top: -25px;
        font-size: 0.7rem;
        font-weight: 600;
        color: #ff8c42;
        white-space: nowrap;
        background: rgba(255, 255, 255, 0.9);
        padding: 2px 4px;
        border-radius: 3px;
        border: 1px solid #ff8c42;
    }

    .chart-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
    }

    .chart-day.today .chart-label {
        color: #ff8c42;
        font-weight: 600;
    }

    .progress-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .progress-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        text-align: center;
        line-height: 1.2;
    }

    /* Quick Actions */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

        .action-btn:hover {
            background-color: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

    .action-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        font-size: 1.5rem;
    }

    .action-btn span {
        font-size: 0.875rem;
        font-weight: 500;
        color: #111827;
    }

    /* Šī nedēļa sadaļa */
    .routine-info-week {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-radius: 0.75rem;
        border: 1px solid #3b82f6;
    }

    .routine-week-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .routine-week-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e40af;
        margin: 0;
    }

    .routine-exercise-count {
        background: #3b82f6;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .routine-exercises-preview {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .preview-exercise {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.75rem;
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #dbeafe;
    }

    .preview-exercise-name {
        font-size: 0.875rem;
        color: #1e293b;
        font-weight: 500;
    }

    .preview-exercise-sets {
        font-size: 0.75rem;
        color: #3b82f6;
        font-weight: 600;
        background: #eff6ff;
        padding: 0.125rem 0.5rem;
        border-radius: 0.25rem;
    }

    .more-exercises {
        text-align: center;
        font-size: 0.75rem;
        color: #6b7280;
        padding: 0.5rem;
        background: #f8fafc;
        border-radius: 0.25rem;
        border: 1px dashed #e5e7eb;
    }

    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .schedule-day {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }

        .schedule-day:hover {
            background-color: #f3f4f6;
        }

        .schedule-day.today {
            background-color: #fff7ed;
            border-color: #ff8c42;
        }

        .schedule-day.active-routine-day {
            background-color: #eff6ff;
            border-color: #3b82f6;
        }

        .schedule-day.today.active-routine-day {
            background-color: #dbeafe;
            border-color: #1d4ed8;
        }

    .day-name {
        font-weight: 500;
        color: #111827;
        min-width: 80px;
        font-size: 0.875rem;
    }

    .schedule-day.today .day-name {
        color: #ff8c42;
        font-weight: 600;
    }

    .day-workout {
        flex: 1;
        color: #6b7280;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .routine-indicator {
        font-size: 1rem;
    }

    .rest-text {
        color: #6b7280;
        font-size: 0.75rem;
        font-style: italic;
        padding: 0.25rem 0.5rem;
    }

    .schedule-note {
        margin-top: 1rem;
        padding: 0.75rem;
        background-color: #eff6ff;
        border-radius: 0.5rem;
        border: 1px solid #dbeafe;
    }

        .schedule-note p {
            color: #1d4ed8;
            font-size: 0.875rem;
            text-align: center;
            margin: 0;
        }

    .left-column,
    .right-column {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
</style>
