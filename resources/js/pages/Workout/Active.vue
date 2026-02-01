<!--<script setup>
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, computed, onMounted, onUnmounted } from 'vue';
    import AppLayout from '@/layouts/AppLayout.vue';

    const props = defineProps({
        workoutSession: {
            type: Object,
            default: () => ({
                id: null,
                name: 'Brīvais treniņš',
                type: 'free',
                status: 'active',
                started_at: new Date().toISOString()
            })
        },
        routine: {
            type: Object,
            default: () => null
        },
        exercises: {
            type: Array,
            default: () => []
        },
        started_at: {
            type: String,
            default: null
        }
    });

    const workoutTime = ref(0);
    const timerInterval = ref(null);
    const isCompleted = ref(false);
    const completedExercises = ref((props.exercises || []).map(ex => ({
        ...ex,
        completed_sets: ex.sets_completed || 0,
        completed_reps: ex.reps_completed || Array(ex.sets || 3).fill(0),
        weight_used: ex.weights_used?.[0] || null,
        notes: ''
    })));

    const workoutName = computed(() => {
        return props.routine?.name || props.workoutSession?.name || 'Brīvais treniņš';
    });

    const routineId = computed(() => {
        return props.routine?.id || null;
    });

    onMounted(() => {
        timerInterval.value = setInterval(() => {
            workoutTime.value++;
        }, 1000);
    });

    const stopTimer = () => {
        if (timerInterval.value) {
            clearInterval(timerInterval.value);
            timerInterval.value = null;
        }
    };

    const formattedTime = computed(() => {
        const minutes = Math.floor(workoutTime.value / 60);
        const seconds = workoutTime.value % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    });

    const completeSet = (exerciseIndex, setIndex) => {
        const exercise = completedExercises.value[exerciseIndex];
        const defaultReps = exercise.reps || 10;
        const reps = prompt(`Cik atkārtojumus izpildījāt ${setIndex + 1}. setā?`, defaultReps);

        if (reps && !isNaN(reps)) {
            exercise.completed_reps[setIndex] = parseInt(reps);
            const allSetsCompleted = exercise.completed_reps.every(rep => rep > 0);
            if (allSetsCompleted) {
                exercise.completed_sets = exercise.sets || 3;
            }
        }
    };

    const addWeight = (exerciseIndex) => {
        const weight = prompt('Kādu svaru izmantojāt? (kg)', '');
        if (weight && !isNaN(weight)) {
            completedExercises.value[exerciseIndex].weight_used = parseFloat(weight);
        }
    };

    const completeWorkout = () => {
        if (confirm('Vai tiešām vēlaties pabeigt treniņu?')) {
            stopTimer();
            const durationMinutes = Math.ceil(workoutTime.value / 60);

            const routeName = routineId.value ? 'workout.complete' : 'workout.free.complete';

            router.post(route(routeName, {
                workoutLog: routineId.value || 'free'
            }), {
                duration_minutes: durationMinutes,
                exercises: completedExercises.value,
                notes: ''
            }, {
                onSuccess: () => {
                    isCompleted.value = true;
                    alert('Treniņš veiksmīgi pabeigts!');
                    router.visit('/dashboard');
                },
                onError: (errors) => {
                    alert('Kļūda saglabājot treniņu: ' + (errors.message || 'Nezināma kļūda'));
                }
            });
        }
    };

    const cancelWorkout = () => {
        if (confirm('Vai tiešām vēlaties atcelt treniņu?')) {
            stopTimer();
            router.visit('/dashboard');
        }
    };

    onUnmounted(() => {
        if (timerInterval.value) {
            clearInterval(timerInterval.value);
        }
    });
</script>

<template>
    <Head :title="`Treniņš: ${workoutName}`" />

    <AppLayout>
        <div class="workout-container">
            <div class="workout-header">
                <h1>{{ workoutName }}</h1>
                <div class="workout-timer">
                    <span class="timer-label">Laiks:</span>
                    <span class="timer-value">{{ formattedTime }}</span>
                </div>
                <button @click="completeWorkout" class="complete-btn">
                    Pabeigt treniņu
                </button>
            </div>

            <div class="workout-content">
                <div class="exercises-list">
                    <div v-for="(exercise, exIndex) in completedExercises"
                         :key="exercise.id || exIndex"
                         class="exercise-card">
                        <div class="exercise-header">
                            <h3>{{ exercise.name }}</h3>
                            <div class="exercise-info">
                                <span>{{ exercise.sets || 3 }} × {{ exercise.reps || 10 }}</span>
                                <span v-if="exercise.rest_seconds">
                                    Atpūta: {{ Math.floor((exercise.rest_seconds || 60) / 60) }}min
                                </span>
                            </div>
                        </div>

                        <div class="sets-container">
                            <div v-for="(set, setIndex) in Array.from({length: exercise.sets || 3})"
                                 :key="setIndex"
                                 class="set-item"
                                 :class="{ 'completed': exercise.completed_reps[setIndex] > 0 }">
                                <span class="set-number">Set {{ setIndex + 1 }}</span>
                                <button @click="completeSet(exIndex, setIndex)"
                                        class="set-btn"
                                        :disabled="exercise.completed_reps[setIndex] > 0">
                                    {{
                                        exercise.completed_reps[setIndex] > 0
                                        ? exercise.completed_reps[setIndex] + ' reps'
                                        : 'Pabeigt'
                                    }}
                                </button>
                            </div>
                        </div>

                        <div class="exercise-actions">
                            <button @click="addWeight(exIndex)" class="weight-btn">
                                {{ exercise.weight_used ? exercise.weight_used + ' kg' : 'Pievienot svaru' }}
                            </button>
                            <input v-model="exercise.notes"
                                   type="text"
                                   placeholder="Piezīmes..."
                                   class="notes-input">
                        </div>
                    </div>
                </div>

                <div class="workout-sidebar">
                    <div class="stats-card">
                        <h3>Treniņa statistika</h3>
                        <div class="stat-item">
                            <span>Kopējais laiks:</span>
                            <span>{{ formattedTime }}</span>
                        </div>
                        <div class="stat-item">
                            <span>Pabeigtie vingrinājumi:</span>
                            <span>{{ completedExercises.filter(e => e.completed_sets === (e.sets || 3)).length }}/{{ completedExercises.length }}</span>
                        </div>
                        <div class="stat-item">
                            <span>Kopējie seti:</span>
                            <span>{{ completedExercises.reduce((sum, e) => sum + e.completed_sets, 0) }}</span>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <button @click="cancelWorkout" class="cancel-btn">
                            Atcelt treniņu
                        </button>
                        <Link v-if="routineId" :href="route('routines.show', routineId)" class="view-routine-btn">
                        Skatīt rutīnu
                        </Link>
                        <Link v-else :href="route('dashboard')" class="view-routine-btn">
                        Atgriezties uz sākumlapu
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    .workout-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .workout-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #ff6b00 0%, #ff8c00 100%);
        border-radius: 1rem;
        color: white;
    }

        .workout-header h1 {
            font-size: 1.75rem;
            font-weight: bold;
            margin: 0;
        }

    .workout-timer {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .timer-label {
        font-size: 0.875rem;
        opacity: 0.8;
    }

    .timer-value {
        font-size: 2rem;
        font-weight: bold;
        font-family: monospace;
    }

    .complete-btn {
        background: white;
        color: #ff6b00;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .complete-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

    .workout-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    @media (max-width: 1024px) {
        .workout-content {
            grid-template-columns: 1fr;
        }
    }

    .exercises-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .exercise-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
    }

    .exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

        .exercise-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

    .exercise-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .sets-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .set-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 0.75rem;
        border: 2px solid #e5e7eb;
        transition: all 0.2s;
    }

        .set-item.completed {
            background: #d1fae5;
            border-color: #10b981;
        }

    .set-number {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
    }

    .set-btn {
        padding: 0.5rem 1rem;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

        .set-btn:hover:not(:disabled) {
            background: #2563eb;
        }

        .set-btn:disabled {
            background: #10b981;
            cursor: default;
        }

    .exercise-actions {
        display: flex;
        gap: 1rem;
    }

    .weight-btn {
        padding: 0.5rem 1rem;
        background: #f3f4f6;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .weight-btn:hover {
            background: #e5e7eb;
        }

    .notes-input {
        flex: 1;
        padding: 0.5rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }

    .workout-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .stats-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
    }

        .stats-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
        }

    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

        .stat-item:last-child {
            border-bottom: none;
        }

    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .cancel-btn {
        padding: 0.75rem 1.5rem;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

        .cancel-btn:hover {
            background: #dc2626;
        }

    .view-routine-btn {
        padding: 0.75rem 1.5rem;
        background: #f3f4f6;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 0.75rem;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s;
    }

        .view-routine-btn:hover {
            background: #e5e7eb;
        }
</style>-->
