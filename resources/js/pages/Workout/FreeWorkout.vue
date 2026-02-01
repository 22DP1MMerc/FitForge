<script setup lang="ts">
    import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import axios from 'axios';

    const page = usePage();

    // ========== PROPS DEFINĒŠANA ==========
    const props = defineProps({
        availableExercises: {
            type: Array,
            default: () => []
        },
        initialWorkout: {
            type: Object,
            default: () => ({})
        },
        workoutSession: {
            type: Object,
            default: null
        }
    });

    // ========== REAKTĪVIE MAINĪGIE ==========
    // Hronometra stāvokļi
    const timer = ref(0);
    const timerRunning = ref(false);
    let timerInterval: NodeJS.Timeout | null = null;

    // Treniņa dati
    const workoutName = ref(props.initialWorkout?.name || 'Brīvais treniņš');
    const workoutExercises = ref<any[]>([]);
    const activeExercise = ref<any>(null);
    const workoutSessionId = ref(props.workoutSession?.id || null);

    // Meklēšana un filtri
    const searchQuery = ref('');
    const selectedMuscleGroups = ref<string[]>([]);

    // ========== COMPUTED PROPERTIES ==========
    // Pašreizējais datums
    const currentDate = computed(() => {
        return new Date().toLocaleDateString('lv-LV', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    });

    // Viegli pieejamas muskuļu grupas
    const muscleGroups = computed(() => {
        const groups = new Set<string>();
        props.availableExercises.forEach((ex: any) => {
            if (ex.muscle_group) {
                groups.add(ex.muscle_group);
            }
        });
        return Array.from(groups).sort();
    });

    // Filtrēti vingrinājumi
    const filteredExercises = computed(() => {
        return props.availableExercises.filter((exercise: any) => {
            const matchesSearch = searchQuery.value === '' ||
                exercise.name.toLowerCase().includes(searchQuery.value.toLowerCase());
            const matchesMuscleGroup = selectedMuscleGroups.value.length === 0 ||
                selectedMuscleGroups.value.includes(exercise.muscle_group);
            return matchesSearch && matchesMuscleGroup;
        });
    });

    // Pabeigto vingrinājumu skaits
    const completedExercisesCount = computed(() => {
        return workoutExercises.value.filter((ex: any) =>
            ex.completedSets?.length === ex.sets
        ).length;
    });

    // Formatētais laiks
    const formattedTime = computed(() => {
        const seconds = timer.value;
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        if (hrs > 0) {
            return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    });

    // ========== FUNKCIJAS ==========
    // Hronometra funkcijas
    const toggleTimer = () => {
        if (timerRunning.value) {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            timerRunning.value = false;
        } else {
            timerRunning.value = true;
            timerInterval = setInterval(() => {
                timer.value++;
            }, 1000);
        }
    };

    const resetTimer = () => {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        timerRunning.value = false;
        timer.value = 0;
        localStorage.removeItem('workout_timer');
        localStorage.removeItem('workout_start_time');
    };

    // Aprēķina pagājušo laiku no starta laika
    const calculateElapsedTime = (startTime: string) => {
        if (!startTime) return 0;
        const start = new Date(startTime);
        const now = new Date();
        return Math.floor((now.getTime() - start.getTime()) / 1000);
    };

    // Sākt treniņu sesiju
    const startWorkoutSession = async (): Promise<string | null> => {
        try {
            const response = await axios.post('/workout/free/start', {
                name: workoutName.value
            });

            if (response.data?.success && response.data.session_id) {
                workoutSessionId.value = response.data.session_id;

                // Saglabā starta laiku
                const startTime = new Date().toISOString();
                localStorage.setItem('workout_start_time', startTime);
                localStorage.removeItem('workout_timer');

                // Start timer
                if (!timerRunning.value) {
                    toggleTimer();
                }

                return response.data.session_id;
            }
            throw new Error(response.data?.message || 'Unknown error');
        } catch (error: any) {
            console.error('Error starting workout session:', error);
            let errorMessage = 'Kļūda sākot treniņu';
            if (error.response?.data?.message) {
                errorMessage += ': ' + error.response.data.message;
            } else if (error.message) {
                errorMessage += ': ' + error.message;
            }
            alert(errorMessage);
            return null;
        }
    };

    // Ielādēt sesijas datus
    const loadWorkoutSession = async () => {
        if (!workoutSessionId.value) return;

        try {
            const response = await axios.get(`/api/workout-session/${workoutSessionId.value}`);
            if (response.data) {
                workoutExercises.value = response.data.exercises.map((exercise: any) => ({
                    id: exercise.id,
                    session_exercise_id: exercise.session_exercise_id,
                    name: exercise.name,
                    muscle_group: exercise.muscle_group,
                    sets: exercise.sets_planned,
                    reps: exercise.reps_planned,
                    weight: 0,
                    currentSet: exercise.sets_completed + 1,
                    completedSets: exercise.reps_completed?.map((reps: number, index: number) => ({
                        reps: reps,
                        weight: exercise.weights_used?.[index] || 0,
                        completedAt: new Date().toISOString()
                    })) || [],
                    restTime: 60
                }));

                if (workoutExercises.value.length > 0 && !activeExercise.value) {
                    setActiveExercise(workoutExercises.value[0]);
                }

                workoutName.value = response.data.name || workoutName.value;

                if (response.data.started_at) {
                    const elapsedTime = calculateElapsedTime(response.data.started_at);
                    timer.value = elapsedTime;
                    localStorage.setItem('workout_timer', elapsedTime.toString());
                    localStorage.setItem('workout_start_time', response.data.started_at);

                    if (!timerRunning.value) {
                        toggleTimer();
                    }
                }
            }
        } catch (error) {
            console.error('Error loading workout session:', error);
        }
    };

    // Pievienot vingrinājumu
    const addExercise = async (exercise: any) => {
        try {
            if (!workoutSessionId.value) {
                const sessionId = await startWorkoutSession();
                if (!sessionId) return;
            }

            // Pārbauda, vai vingrinājums jau eksistē
            const existingIndex = workoutExercises.value.findIndex(ex => ex.id === exercise.id);
            if (existingIndex !== -1) {
                setActiveExercise(workoutExercises.value[existingIndex]);
                return;
            }

            // Pievieno backendā
            await axios.post(`/workout/${workoutSessionId.value}/exercises`, {
                exercise_id: exercise.id,
                sets_planned: 3,
                reps_planned: 10
            });

            // Atjaunina visu sarakstu no servera
            await loadWorkoutSession();

        } catch (error: any) {
            console.error('Error adding exercise:', error);
            if (error.response?.status === 404) {
                alert('Treniņa sesija nav atrasta. Lūdzu, sāciet jaunu treniņu.');
            } else {
                alert('Kļūda pievienojot vingrinājumu: ' + (error.response?.data?.message || error.message));
            }
        }
    };

    // Noņemt vingrinājumu
    const removeExercise = async (index: number) => {
        const removedExercise = workoutExercises.value[index];

        try {
            if (removedExercise.session_exercise_id && workoutSessionId.value) {
                await axios.delete(`/workout/${workoutSessionId.value}/exercises/${removedExercise.session_exercise_id}`);
            }

            workoutExercises.value.splice(index, 1);

            // Ja tika noņemts aktīvais vingrinājums
            if (activeExercise.value && activeExercise.value.id === removedExercise.id) {
                if (workoutExercises.value.length > 0) {
                    setActiveExercise(workoutExercises.value[0]);
                } else {
                    activeExercise.value = null;
                }
            }
        } catch (error) {
            console.error('Error removing exercise:', error);
            alert('Kļūda noņemot vingrinājumu');
        }
    };

    const setActiveExercise = (exercise: any) => {
        activeExercise.value = { ...exercise };
    };

    const updateReps = (change: number) => {
        if (activeExercise.value) {
            const newReps = activeExercise.value.reps + change;
            if (newReps >= 1 && newReps <= 50) {
                activeExercise.value.reps = newReps;

                const index = workoutExercises.value.findIndex((ex: any) => ex.id === activeExercise.value.id);
                if (index !== -1) {
                    workoutExercises.value[index].reps = newReps;
                }
            }
        }
    };

    const updateWeight = (change: number) => {
        if (activeExercise.value) {
            const newWeight = parseFloat((activeExercise.value.weight + change).toFixed(1));
            if (newWeight >= 0) {
                activeExercise.value.weight = newWeight;

                const index = workoutExercises.value.findIndex((ex: any) => ex.id === activeExercise.value.id);
                if (index !== -1) {
                    workoutExercises.value[index].weight = newWeight;
                }
            }
        }
    };

    // Pabeigt setu
    const completeSet = async () => {
        if (!activeExercise.value) return;

        try {
            const completedSet = {
                reps: activeExercise.value.reps,
                weight: activeExercise.value.weight,
                completedAt: new Date().toISOString()
            };

            const index = workoutExercises.value.findIndex((ex: any) => ex.id === activeExercise.value.id);
            if (index !== -1) {
                // Saglabā backendā
                if (workoutSessionId.value && workoutExercises.value[index].session_exercise_id) {
                    await axios.post(
                        `/workout/${workoutSessionId.value}/exercises/${workoutExercises.value[index].session_exercise_id}/set`,
                        {
                            set_index: workoutExercises.value[index].completedSets.length,
                            reps: completedSet.reps,
                            weight: completedSet.weight
                        }
                    );
                }

                // Pievieno lokāli
                workoutExercises.value[index].completedSets.push(completedSet);
                workoutExercises.value[index].currentSet++;

                if (workoutExercises.value[index].currentSet <= workoutExercises.value[index].sets) {
                    // Atjaunina nākamo setu
                    workoutExercises.value[index].reps = 10;
                    workoutExercises.value[index].weight = 0;
                    activeExercise.value = { ...workoutExercises.value[index] };
                } else {
                    // Pāriet uz nākamo vingrinājumu
                    const nextIndex = (index + 1) % workoutExercises.value.length;
                    if (workoutExercises.value.length > 0) {
                        setActiveExercise(workoutExercises.value[nextIndex]);
                    } else {
                        activeExercise.value = null;
                    }
                }
            }
        } catch (error: any) {
            console.error('Error completing set:', error);
            alert('Kļūda saglabājot setu: ' + (error.response?.data?.message || error.message));
        }
    };

    // Pabeigt treniņu
    const completeWorkout = async () => {
        if (workoutExercises.value.length === 0) {
            alert('Lūdzu, pievieno vismaz vienu vingrinājumu!');
            return;
        }

        const totalSets = workoutExercises.value.reduce((sum: number, ex: any) =>
            sum + (ex.completedSets?.length || 0), 0);

        if (!confirm(`Vai tiešām vēlies pabeigt treniņu?\n\n` +
            `Treniņa nosaukums: ${workoutName.value}\n` +
            `Ilgums: ${formattedTime.value}\n` +
            `Vingrinājumi: ${workoutExercises.value.length}\n` +
            `Pabeigtie seti: ${totalSets}`)) {
            return;
        }

        if (timerRunning.value) {
            toggleTimer();
        }

        try {
            if (workoutSessionId.value) {
                // Izmantojiet pareizo maršrutu
                const response = await axios.post(`/workout/${workoutSessionId.value}/complete`, {
                    duration_minutes: Math.round(timer.value / 60),
                    calories_burned: Math.round(timer.value / 60 * 5),
                    notes: ''
                });

                // Pārbaudiet atbildi
                console.log('Response:', response);

                if (response.data.success) {
                    localStorage.removeItem('workout_timer');
                    localStorage.removeItem('workout_start_time');
                    router.visit('/dashboard');
                } else {
                    alert('Kļūda: ' + (response.data.message || 'Nezināma kļūda'));
                }
            }
        } catch (error: any) {
            console.error('Complete workout error:', error);
            let errorMessage = 'Kļūda pabeidzot treniņu';
            if (error.response?.data?.message) {
                errorMessage += ': ' + error.response.data.message;
            } else if (error.message) {
                errorMessage += ': ' + error.message;
            }
            alert(errorMessage);
        }
    };

    const resetWorkout = async () => {
        if (confirm('Vai tiešām vēlies atcelt treniņu? Visi dati tiks zaudēti.')) {
            try {
                if (workoutSessionId.value) {
                    const response = await axios.post(`/workout/${workoutSessionId.value}/cancel`);

                    if (response.data.success) {
                        workoutExercises.value = [];
                        activeExercise.value = null;
                        workoutName.value = 'Brīvais treniņš - ' + new Date().toLocaleDateString('lv-LV');
                        workoutSessionId.value = null;
                        resetTimer();
                        localStorage.removeItem('workout_timer');
                        localStorage.removeItem('workout_start_time');

                        router.visit('/dashboard');
                    }
                }
            } catch (error: any) {
                console.error('Error canceling workout:', error);
                alert('Kļūda atceļot treniņu: ' + (error.response?.data?.message || error.message));
            }
        }
    };

    const toggleMuscleGroup = (group: string) => {
        const index = selectedMuscleGroups.value.indexOf(group);
        if (index > -1) {
            selectedMuscleGroups.value.splice(index, 1);
        } else {
            selectedMuscleGroups.value.push(group);
        }
    };

    // Saglabā hronometru localStorage
    const saveTimerToLocalStorage = () => {
        if (timerRunning.value) {
            localStorage.setItem('workout_timer', timer.value.toString());
        }
    };

    // Ielādē hronometru no localStorage
    const loadTimerFromLocalStorage = () => {
        const savedTimer = localStorage.getItem('workout_timer');
        const savedStartTime = localStorage.getItem('workout_start_time');

        if (savedTimer && savedStartTime) {
            const startTime = new Date(savedStartTime);
            const now = new Date();
            const hoursDiff = (now.getTime() - startTime.getTime()) / (1000 * 60 * 60);

            if (hoursDiff < 24) {
                const elapsedTime = calculateElapsedTime(savedStartTime);
                timer.value = elapsedTime;
                return true;
            } else {
                localStorage.removeItem('workout_timer');
                localStorage.removeItem('workout_start_time');
            }
        }
        return false;
    };

    // ========== LIFECYCLE HOOKS ==========
    onMounted(() => {
        console.log('Component mounted with props:', props);

        // Mēģina ielādēt hronometru no localStorage
        const hasSavedTimer = loadTimerFromLocalStorage();

        // Pārbauda, vai lapā ienāk ar aktīvu sesiju
        if (props.workoutSession) {
            workoutSessionId.value = props.workoutSession.id;
            console.log('Workout session from props:', props.workoutSession);

            // Ielādē sesijas datus
            loadWorkoutSession();

            // Iestata hronometru no sesijas starta laika
            if (props.workoutSession.started_at) {
                const elapsedTime = calculateElapsedTime(props.workoutSession.started_at);
                timer.value = elapsedTime;
                console.log('Timer set from session start time:', elapsedTime);
            }

        } else if (hasSavedTimer) {
            console.log('Timer loaded from localStorage:', timer.value);
            if (!timerRunning.value) {
                toggleTimer();
            }
        }

        // Sāk hronometru automātiski, ja ir sesija un nav sākts
        if (!timerRunning.value && workoutSessionId.value) {
            console.log('Starting timer for session:', workoutSessionId.value);
            toggleTimer();
        }

        // Saglabā timer ik pēc 30 sekundēm
        const saveInterval = setInterval(saveTimerToLocalStorage, 30000);

        // Notīrām intervālu, kad komponents tiek noņemts
        return () => {
            clearInterval(saveInterval);
            if (timerInterval) {
                clearInterval(timerInterval);
            }
        };
    });

    // Saglabā timer, kad tas mainās
    watch(timer, () => {
        if (timerRunning.value && timer.value % 30 === 0) {
            saveTimerToLocalStorage();
        }
    });

    onUnmounted(() => {
        if (timerInterval) {
            clearInterval(timerInterval);
        }
    });
</script>

<template>
    <AppLayout>
        <div class="dashboard-container">
            <div class="dashboard">
                <!-- Header -->
                <div class="welcome-header">
                    <div class="header-content">
                        <h1>Brīvais treniņš</h1>
                        <p>Treniņš bez rutīnas</p>
                    </div>
                    <div class="date-display">
                        <svg class="date-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="date-text">{{ currentDate }}</span>
                    </div>
                </div>

                <!-- Hronometrs -->
                <div class="timer-section">
                    <h2 class="timer-title">Treniņa laiks</h2>
                    <div class="timer-display">{{ formattedTime }}</div>
                    <div class="timer-controls">
                        <button @click="toggleTimer" class="timer-btn" :class="timerRunning ? 'pause-btn' : 'start-btn'">
                            {{ timerRunning ? '⏸️ Pauze' : '▶️ Sākt' }}
                        </button>
                        <button @click="resetTimer" class="timer-btn reset-btn">
                            🔄 Atiestatīt
                        </button>
                    </div>
                </div>

                <!-- Galvenais saturs -->
                <div class="workout-content">
                    <!-- Kreisā kolonna -->
                    <div class="left-panel">
                        <!-- Treniņa nosaukums -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Treniņa informācija</h2>
                            </div>
                            <div class="card-body">
                                <input v-model="workoutName"
                                       type="text"
                                       class="workout-name-input"
                                       placeholder="Ievadi treniņa nosaukumu...">
                            </div>
                        </div>

                        <!-- Vingrinājumu meklēšana -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Pieejamie vingrinājumi</h2>
                                <span class="exercise-count">{{ availableExercises.length }} pieejami</span>
                            </div>
                            <div class="card-body">
                                <input v-model="searchQuery"
                                       type="text"
                                       class="search-input"
                                       placeholder="Meklēt vingrinājumus...">

                                <div class="filter-buttons">
                                    <button v-for="group in muscleGroups" :key="group"
                                            @click="toggleMuscleGroup(group)"
                                            class="filter-btn"
                                            :class="{ 'active-filter': selectedMuscleGroups.includes(group) }">
                                        {{ group }}
                                    </button>
                                </div>

                                <div class="exercise-list">
                                    <div v-for="exercise in filteredExercises" :key="exercise.id"
                                         class="exercise-item"
                                         @click="addExercise(exercise)">
                                        <div class="exercise-info">
                                            <div class="exercise-name">{{ exercise.name }}</div>
                                            <div class="exercise-muscle">{{ exercise.muscle_group }}</div>
                                        </div>
                                        <button class="add-exercise-btn">+ Pievienot</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Labā kolonna -->
                    <div class="right-panel">
                        <!-- Aktīvais vingrinājums -->
                        <div v-if="activeExercise" class="card active-exercise-card">
                            <div class="card-body">
                                <div class="active-exercise-header">
                                    <div class="exercise-header-left">
                                        <div class="exercise-title">{{ activeExercise.name }}</div>
                                        <div class="exercise-muscle-badge">{{ activeExercise.muscle_group }}</div>
                                    </div>
                                    <div class="exercise-sets-info">
                                        Seti: {{ activeExercise.currentSet }}/{{ activeExercise.sets }}
                                    </div>
                                </div>

                                <!-- Setu statistika -->
                                <div class="set-stats">
                                    <div class="stat-box">
                                        <div class="stat-value">{{ activeExercise.currentSet }}</div>
                                        <div class="stat-label">Pašreizējais sets</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">{{ activeExercise.reps }}</div>
                                        <div class="stat-label">Atkārtojumi</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value">{{ activeExercise.weight || 0 }}</div>
                                        <div class="stat-label">Svars (kg)</div>
                                    </div>
                                </div>

                                <!-- Setu kontrole -->
                                <div class="set-controls">
                                    <h3 class="control-title">Setu reģistrēšana</h3>

                                    <div class="control-group">
                                        <div class="control-label">Atkārtojumi</div>
                                        <div class="control-input-group">
                                            <button @click="updateReps(-1)" class="control-btn">-</button>
                                            <input v-model.number="activeExercise.reps"
                                                   type="number"
                                                   class="control-input">
                                            <button @click="updateReps(1)" class="control-btn">+</button>
                                        </div>
                                    </div>

                                    <div class="control-group">
                                        <div class="control-label">Svars (kg)</div>
                                        <div class="control-input-group">
                                            <button @click="updateWeight(-2.5)" class="control-btn">-2.5</button>
                                            <input v-model.number="activeExercise.weight"
                                                   type="number"
                                                   step="0.5"
                                                   class="control-input">
                                            <button @click="updateWeight(2.5)" class="control-btn">+2.5</button>
                                        </div>
                                    </div>

                                    <button @click="completeSet"
                                            class="complete-exercise-btn">
                                        Pabeigt setu
                                    </button>
                                </div>

                                <!-- Pabeigtie seti -->
                                <div v-if="activeExercise.completedSets.length > 0" class="completed-sets">
                                    <h4 class="completed-sets-title">Pabeigtie seti:</h4>
                                    <div class="sets-grid">
                                        <div v-for="(set, index) in activeExercise.completedSets" :key="index"
                                             class="set-badge">
                                            <div class="set-reps">{{ set.reps }}x</div>
                                            <div class="set-weight">{{ set.weight }}kg</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Treniņa plāns -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Treniņa plāns</h2>
                                <span class="exercise-count">{{ workoutExercises.length }} vingrinājumi</span>
                            </div>
                            <div class="card-body">
                                <div v-if="workoutExercises.length > 0" class="workout-plan-list">
                                    <div v-for="(exercise, index) in workoutExercises" :key="exercise.id"
                                         class="plan-exercise"
                                         :class="{ 'active-plan': exercise.id === activeExercise?.id }"
                                         @click="setActiveExercise(exercise)">
                                        <div class="plan-exercise-info">
                                            <div class="exercise-number">{{ index + 1 }}</div>
                                            <div class="exercise-details">
                                                <div class="plan-exercise-name">{{ exercise.name }}</div>
                                                <div class="plan-exercise-muscle">{{ exercise.muscle_group }}</div>
                                            </div>
                                        </div>
                                        <div class="plan-exercise-actions">
                                            <div class="exercise-sets-reps">{{ exercise.sets }}x{{ exercise.reps }}</div>
                                            <div v-if="exercise.completedSets.length > 0"
                                                 class="exercise-progress">
                                                {{ exercise.completedSets.length }}/{{ exercise.sets }}
                                            </div>
                                            <button @click.stop="removeExercise(index)"
                                                    class="remove-exercise-btn">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="empty-state">
                                    <div class="empty-icon">🏋️</div>
                                    <div class="empty-title">Vēl nav pievienotu vingrinājumu</div>
                                    <div class="empty-description">Izvēlies vingrinājumus no kreisās kolonnas</div>
                                </div>
                            </div>
                        </div>

                        <!-- Darbības pogas -->
                        <div class="action-buttons">
                            <button @click="completeWorkout"
                                    :disabled="workoutExercises.length === 0"
                                    class="action-btn complete-workout-btn"
                                    :class="{ 'disabled-btn': workoutExercises.length === 0 }">
                                <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Pabeigt treniņu
                            </button>
                            <button @click="resetWorkout"
                                    class="action-btn reset-workout-btn">
                                <svg class="action-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Dzēst treniņu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Apakšējā statusa josla -->
                <div class="status-bar">
                    <div class="status-content">
                        <div class="status-info">
                            <div class="status-indicator">
                                <div class="status-dot"></div>
                                <span class="status-text">Treniņš aktīvs</span>
                            </div>
                            <span class="status-count">{{ workoutExercises.length }} vingrinājumi</span>
                            <span class="status-count">{{ completedExercisesCount }} pabeigti</span>
                        </div>
                        <div class="status-timer">{{ formattedTime }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

    .dashboard-container {
        min-height: 100vh;
        background-color: #f3f4f6;
    }

    .dashboard {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem;
    }


    /* Galvenie stili */
    .dashboard-container {
        min-height: 100vh;
        background-color: #f3f4f6;
    }

    .dashboard {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1.5rem;
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

    .header-content h1 {
        font-size: 1.875rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .header-content p {
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
    }

    .date-icon {
        width: 1.25rem;
        height: 1.25rem;
    }

    .date-text {
        font-size: 0.875rem;
        font-weight: 500;
    }

    /* Hronometrs */
    .timer-section {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .timer-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .timer-display {
        font-size: 3rem;
        font-weight: bold;
        font-family: monospace;
        text-align: center;
        margin-bottom: 1.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .timer-controls {
        display: flex;
        justify-content: center;
        gap: 1rem;
    }

    .timer-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .start-btn {
        background: white;
        color: #4f46e5;
    }

        .start-btn:hover {
            background: #f3f4f6;
            transform: translateY(-1px);
        }

    .pause-btn {
        background: #ef4444;
        color: white;
    }

        .pause-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

    .reset-btn {
        background: #6b7280;
        color: white;
    }

        .reset-btn:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

    /* Galvenais saturs */
    .workout-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .workout-content {
            grid-template-columns: 1fr;
        }
    }

    /* Kartiņas */
    .card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

        .card:last-child {
            margin-bottom: 0;
        }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
    }

    .exercise-count {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Treniņa nosaukuma ievade */
    .workout-name-input {
        width: 100%;
        padding: 0.75rem 1rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 1rem;
        color: #111827;
        transition: all 0.2s;
    }

        .workout-name-input:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
            background: white;
        }

    /* Meklēšana */
    .search-input {
        width: 100%;
        padding: 0.75rem 1rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: #111827;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

        .search-input:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
            background: white;
        }

    /* Filtru pogas */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .filter-btn {
        padding: 0.375rem 0.75rem;
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .active-filter {
        background: #ff8c42;
        color: white;
        border-color: #ff8c42;
    }

    .filter-btn:hover:not(.active-filter) {
        background: #e5e7eb;
        color: #374151;
    }

    /* Vingrinājumu saraksts */
    .exercise-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .exercise-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .exercise-item:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

    .exercise-info {
        flex: 1;
    }

    .exercise-name {
        font-weight: 500;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .exercise-muscle {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .add-exercise-btn {
        padding: 0.25rem 0.75rem;
        background: #ff8c42;
        color: white;
        border: none;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
    }

        .add-exercise-btn:hover {
            background: #e65c00;
        }

    /* Aktīvais vingrinājums */
    .active-exercise-card {
        border: 2px solid #ff8c42;
        background: #fff7ed;
    }

    .active-exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .exercise-header-left {
        flex: 1;
    }

    .exercise-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .exercise-muscle-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #ff8c42;
        color: white;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .exercise-sets-info {
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Setu statistika */
    .set-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-box {
        background: white;
        border-radius: 0.75rem;
        padding: 1rem;
        text-align: center;
        border: 1px solid #e5e7eb;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Setu kontrole */
    .control-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 1rem;
    }

    .control-group {
        margin-bottom: 1rem;
    }

    .control-label {
        display: block;
        color: #6b7280;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .control-input-group {
        display: flex;
        gap: 0.25rem;
    }

    .control-btn {
        padding: 0.5rem 1rem;
        background: #e5e7eb;
        color: #374151;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .control-btn:hover {
            background: #d1d5db;
        }

    .control-input {
        flex: 1;
        padding: 0.5rem;
        text-align: center;
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 1rem;
    }

        .control-input:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

    .complete-exercise-btn {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(to right, #10b981, #059669);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: all 0.2s;
    }

        .complete-exercise-btn:hover {
            background: linear-gradient(to right, #059669, #047857);
            transform: translateY(-1px);
        }

    /* Pabeigtie seti */
    .completed-sets {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .completed-sets-title {
        font-size: 1rem;
        color: #6b7280;
        margin-bottom: 1rem;
    }

    .sets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 0.75rem;
    }

    .set-badge {
        background: #f9fafb;
        border-radius: 0.5rem;
        padding: 0.75rem;
        text-align: center;
        border: 1px solid #e5e7eb;
    }

    .set-reps {
        font-weight: bold;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .set-weight {
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Treniņa plāns */
    .workout-plan-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .plan-exercise {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: #f9fafb;
        border: 2px solid transparent;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .active-plan {
        background: #fff7ed;
        border-color: #ff8c42;
    }

    .plan-exercise:hover:not(.active-plan) {
        background: #f3f4f6;
        border-color: #d1d5db;
    }

    .plan-exercise-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .exercise-number {
        width: 2rem;
        height: 2rem;
        background: #e5e7eb;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #111827;
    }

    .exercise-details {
        flex: 1;
    }

    .plan-exercise-name {
        font-weight: 500;
        color: #111827;
        margin-bottom: 0.125rem;
    }

    .plan-exercise-muscle {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .plan-exercise-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .exercise-sets-reps {
        font-weight: 600;
        color: #111827;
    }

    .exercise-progress {
        padding: 0.25rem 0.5rem;
        background: #dcfce7;
        color: #166534;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .remove-exercise-btn {
        width: 1.5rem;
        height: 1.5rem;
        background: transparent;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        transition: color 0.2s;
    }

        .remove-exercise-btn:hover {
            color: #ef4444;
        }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .empty-description {
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Darbības pogas */
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .action-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 1rem;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .complete-workout-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

        .complete-workout-btn:hover:not(.disabled-btn) {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
        }

    .disabled-btn {
        opacity: 0.5;
        cursor: not-allowed;
    }

        .disabled-btn:hover {
            transform: none !important;
        }

    .reset-workout-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

        .reset-workout-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-1px);
        }

    .action-icon {
        width: 1.25rem;
        height: 1.25rem;
    }

    /* Statusa josla */
    .status-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e5e7eb;
        padding: 0.75rem 0;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }

    .status-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .status-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #10b981;
        font-weight: 500;
    }

    .status-dot {
        width: 0.5rem;
        height: 0.5rem;
        background-color: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .status-text {
        color: #10b981;
    }

    .status-count {
        padding: 0.25rem 0.5rem;
        background: #f3f4f6;
        border-radius: 0.25rem;
    }

    .status-timer {
        font-size: 1.125rem;
        font-weight: bold;
        font-family: monospace;
        color: #111827;
    }

    /* Scrollbar stils */
    .exercise-list::-webkit-scrollbar {
        width: 6px;
    }

    .exercise-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .exercise-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

        .exercise-list::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
</style>
