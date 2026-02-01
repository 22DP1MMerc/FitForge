<template>
    <AppLayout>
        <div class="workout-detail">
            <!-- Back button -->
            <div class="back-nav">
                <Link :href="route('workout-logs.index')" class="back-link">
                ← Atpakaļ uz sarakstu
                </Link>
            </div>

            <!-- Header -->
            <div class="detail-header">
                <h1 class="workout-title">{{ workoutLog.name }}</h1>
                <div class="workout-meta">
                    <span class="meta-item">📅 {{ formatDate(workoutLog.completed_at) }}</span>
                    <span class="meta-item">⏱️ {{ workoutLog.duration_minutes }} min</span>
                    <span v-if="workoutLog.calories_burned" class="meta-item">
                        🔥 {{ workoutLog.calories_burned }} cal
                    </span>
                    <span v-if="workoutLog.routine" class="routine-tag">
                        {{ workoutLog.routine.name }}
                    </span>
                </div>
                <div class="header-actions">
                    <button @click="exportWorkout" class="btn-export">📥 Eksportēt</button>
                    <button @click="confirmDelete" class="btn-delete">🗑️ Dzēst</button>
                </div>
            </div>

            <!-- Treniņa statistika -->
            <div class="quick-stats">
                <div class="stat-box">
                    <div class="stat-value">{{ stats.total_sets }}</div>
                    <div class="stat-label">Kopējie seti</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ stats.total_reps }}</div>
                    <div class="stat-label">Kopējie atkārtojumi</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ stats.total_weight }} kg</div>
                    <div class="stat-label">Kopējais svars</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">{{ stats.average_weight_per_set }} kg</div>
                    <div class="stat-label">Vidējais svars/setam</div>
                </div>
            </div>

            <div class="detail-content">
                <!-- Vingrinājumi -->
                <div class="exercises-section">
                    <h2 class="section-title">
                        Vingrinājumi
                        <span class="count-badge">{{ workoutLog.log_exercises?.length || 0 }}</span>
                    </h2>

                    <div class="exercises-list">
                        <div v-for="(logExercise, index) in workoutLog.log_exercises" :key="logExercise.id" class="exercise-card">
                            <div class="exercise-header">
                                <div>
                                    <h3 class="exercise-name">{{ logExercise.exercise?.name }}</h3>
                                    <div class="exercise-info">
                                        <span v-if="logExercise.exercise?.muscle_group" class="muscle-group">
                                            💪 {{ logExercise.exercise.muscle_group }}
                                        </span>
                                        <span class="planned">
                                            Plānots: {{ logExercise.sets_planned }}x{{ logExercise.reps_planned }}
                                        </span>
                                    </div>
                                </div>
                                <div class="completed-info">
                                    <div class="completed-count">{{ logExercise.sets_completed }} seti</div>
                                    <div class="label">pabeigti</div>
                                </div>
                            </div>

                            <!-- Setu rezultāti -->
                            <div class="sets-section">
                                <h4 class="sets-title">Setu rezultāti:</h4>
                                <div class="sets-grid">
                                    <div v-for="(set, setIndex) in getSetsData(logExercise)" :key="setIndex" class="set-box">
                                        <div class="set-number">Set {{ setIndex + 1 }}</div>
                                        <div class="set-data">
                                            <span class="reps">{{ set.reps }}x</span>
                                            <span class="weight">{{ set.weight }} kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Piezīmes -->
                            <div v-if="logExercise.notes" class="exercise-notes">
                                <div class="notes-label">📝 Piezīmes:</div>
                                <p class="notes-content">{{ logExercise.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Muskuļu grupu statistika -->
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Muskuļu grupas</h3>
                        <div class="muscle-groups">
                            <div v-for="(count, muscleGroup) in stats.muscle_groups" :key="muscleGroup" class="muscle-item">
                                <div class="muscle-name">{{ muscleGroup }}</div>
                                <div class="muscle-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill"
                                             :style="{ width: calculateMuscleGroupPercentage(count) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="muscle-count">{{ count }} seti</div>
                            </div>
                        </div>
                    </div>

                    <!-- Līdzīgie treniņi -->
                    <div v-if="similarWorkouts.length > 0" class="sidebar-card">
                        <h3 class="sidebar-title">Līdzīgi treniņi</h3>
                        <div class="similar-workouts">
                            <div v-for="similar in similarWorkouts" :key="similar.id" class="similar-workout">
                                <div class="similar-info">
                                    <div class="similar-name">{{ similar.name }}</div>
                                    <div class="similar-date">{{ formatDate(similar.completed_at) }}</div>
                                </div>
                                <Link :href="route('workout-logs.show', similar.id)" class="view-link">
                                Skatīt →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Papildus informācija -->
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Papildus informācija</h3>
                        <div class="additional-info">
                            <div class="info-row">
                                <span class="info-label">Vidējie atkārtojumi setā:</span>
                                <span class="info-value">{{ stats.average_reps_per_set }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Kopējais treniņa svars:</span>
                                <span class="info-value">{{ stats.total_weight }} kg</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Vidējais svars setā:</span>
                                <span class="info-value">{{ stats.average_weight_per_set }} kg</span>
                            </div>
                            <div v-if="workoutLog.calories_burned" class="info-row">
                                <span class="info-label">Pazudušās kalorijas:</span>
                                <span class="info-value">{{ workoutLog.calories_burned }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dzēšanas dialogs -->
            <div v-if="showDeleteModal" class="modal">
                <div class="modal-content">
                    <h3>Dzēst treniņu?</h3>
                    <p>Vai tiešām vēlaties dzēst šo treniņu? Šī darbība nevar tikt atcelta.</p>
                    <div class="modal-buttons">
                        <button @click="showDeleteModal = false" class="btn-cancel">Atcelt</button>
                        <button @click="deleteWorkout" class="btn-confirm">Dzēst</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';

    // Props
    const props = defineProps({
        workoutLog: Object,
        stats: Object,
        similarWorkouts: Array
    });

    // Reactive state
    const showDeleteModal = ref(false);

    // Formatēšanas funkcijas
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('lv-LV', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    // Iegūst setu datus
    const getSetsData = (logExercise: any) => {
        const sets = [];
        const reps = logExercise.reps_completed || [];
        const weights = logExercise.weights_used || [];

        for (let i = 0; i < logExercise.sets_completed; i++) {
            const rep = reps[i] || 0;
            let weight = 0;

            if (Array.isArray(weights[i])) {
                weight = weights[i]?.weight || 0;
            } else {
                weight = weights[i] || 0;
            }

            sets.push({
                reps: rep,
                weight: weight
            });
        }

        return sets;
    };

    // Aprēķina muskuļu grupas procentus
    const calculateMuscleGroupPercentage = (count: number) => {
        const total = Object.values(props.stats.muscle_groups).reduce((sum: number, val: any) => sum + val, 0);
        return total > 0 ? Math.round((count / total) * 100) : 0;
    };

    // Eksportēšana
    const exportWorkout = async () => {
        try {
            const response = await fetch(route('workout-logs.export', props.workoutLog.id));
            const data = await response.json();

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `trenins-${props.workoutLog.id}-${new Date(props.workoutLog.completed_at).toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

        } catch (error) {
            console.error('Export error:', error);
            alert('Kļūda eksportējot treniņu');
        }
    };

    // Dzēšana
    const confirmDelete = () => {
        showDeleteModal.value = true;
    };

    const deleteWorkout = async () => {
        try {
            await router.delete(route('workout-logs.destroy', props.workoutLog.id));
        } catch (error) {
            console.error('Delete error:', error);
            alert('Kļūda dzēšot treniņu');
        }
    };
</script>

<style scoped>
    .workout-detail {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Back navigation */
    .back-nav {
        margin-bottom: 20px;
    }

    .back-link {
        display: inline-block;
        color: #666;
        text-decoration: none;
        font-size: 14px;
        padding: 8px 0;
        transition: color 0.2s;
    }

        .back-link:hover {
            color: #ff6b00;
        }

    /* Header */
    .detail-header {
        margin-bottom: 30px;
    }

    .workout-title {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
    }

    .workout-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        margin-bottom: 20px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #666;
        font-size: 14px;
    }

    .routine-tag {
        background: #e6f7ff;
        color: #007acc;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .btn-export, .btn-delete {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-export {
        background: #007acc;
        color: white;
    }

        .btn-export:hover {
            background: #0066b3;
        }

    .btn-delete {
        background: #dc3545;
        color: white;
    }

        .btn-delete:hover {
            background: #c82333;
        }

    /* Quick stats */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .stat-box {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #eaeaea;
        text-align: center;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #ff6b00;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #666;
        font-size: 14px;
    }

    /* Main content */
    .detail-content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    @media (max-width: 1024px) {
        .detail-content {
            grid-template-columns: 1fr;
        }
    }

    /* Exercises section */
    .exercises-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .count-badge {
        background: #ff6b00;
        color: white;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
    }

    .exercises-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .exercise-card {
        border: 1px solid #eaeaea;
        border-radius: 10px;
        padding: 20px;
    }

    .exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .exercise-name {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .exercise-info {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .muscle-group {
        font-size: 13px;
        color: #007acc;
        background: #e6f7ff;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .planned {
        font-size: 13px;
        color: #666;
    }

    .completed-info {
        text-align: right;
    }

    .completed-count {
        font-size: 20px;
        font-weight: 700;
        color: #007acc;
        margin-bottom: 4px;
    }

    .completed-info .label {
        font-size: 12px;
        color: #888;
    }

    /* Sets section */
    .sets-section {
        margin-top: 20px;
    }

    .sets-title {
        font-size: 14px;
        font-weight: 600;
        color: #555;
        margin-bottom: 12px;
    }

    .sets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
    }

    .set-box {
        background: #f8f9fa;
        border: 1px solid #eaeaea;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }

    .set-number {
        font-size: 11px;
        color: #888;
        margin-bottom: 6px;
    }

    .set-data {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

        .set-data .reps {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .set-data .weight {
            font-size: 14px;
            color: #666;
        }

    /* Exercise notes */
    .exercise-notes {
        margin-top: 15px;
        padding: 12px;
        background: #fff9e6;
        border-radius: 8px;
        border: 1px solid #ffe58f;
    }

    .notes-label {
        font-size: 13px;
        font-weight: 600;
        color: #d48806;
        margin-bottom: 6px;
    }

    .notes-content {
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }

    /* Sidebar */
    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .sidebar-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #eaeaea;
    }

    .sidebar-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    /* Muscle groups */
    .muscle-groups {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .muscle-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .muscle-name {
        font-size: 13px;
        color: #333;
        width: 100px;
        flex-shrink: 0;
    }

    .muscle-progress {
        flex: 1;
    }

    .progress-bar {
        height: 6px;
        background: #eaeaea;
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #007acc, #005fa3);
        border-radius: 3px;
    }

    .muscle-count {
        font-size: 13px;
        font-weight: 500;
        color: #333;
        width: 50px;
        text-align: right;
    }

    /* Similar workouts */
    .similar-workouts {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .similar-workout {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        border: 1px solid #eaeaea;
        border-radius: 8px;
        transition: border-color 0.2s;
    }

        .similar-workout:hover {
            border-color: #007acc;
        }

    .similar-info {
        flex: 1;
    }

    .similar-name {
        font-size: 14px;
        font-weight: 500;
        color: #333;
        margin-bottom: 4px;
    }

    .similar-date {
        font-size: 12px;
        color: #888;
    }

    .view-link {
        font-size: 13px;
        color: #007acc;
        text-decoration: none;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 0.2s;
    }

        .view-link:hover {
            background: #e6f7ff;
        }

    /* Additional info */
    .additional-info {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

        .info-row:last-child {
            border-bottom: none;
        }

    .info-label {
        font-size: 13px;
        color: #666;
    }

    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    /* Modal (kopīgs ar Index) */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

        .modal-content h3 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .modal-content p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-cancel {
        padding: 10px 20px;
        background: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 6px;
        color: #666;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-cancel:hover {
            background: #eaeaea;
            color: #333;
        }

    .btn-confirm {
        padding: 10px 20px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

        .btn-confirm:hover {
            background: #c82333;
        }
</style>
