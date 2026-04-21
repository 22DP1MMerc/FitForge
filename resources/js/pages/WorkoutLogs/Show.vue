<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    workoutLog:      Object,
    stats:           Object,
    similarWorkouts: Array
});

const showDeleteModal = ref(false);

const formatDate = (d: string) => new Date(d).toLocaleDateString('lv-LV', {
    year: 'numeric', month: 'long', day: 'numeric'
});

const formatTime = (d: string) => new Date(d).toLocaleTimeString('lv-LV', {
    hour: '2-digit', minute: '2-digit'
});

// Sekundes uz lasāmu formātu
const formatDuration = (seconds: number) => {
    if (!seconds) return '—';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    if (m > 0) return s > 0 ? `${m}min ${s}s` : `${m}min`;
    return `${s}s`;
};

// Izmanto is_cardio karogu ko nosūta kontrolieris
const isCardio = (logExercise: any) => {
    return logExercise.is_cardio === true;
};

// Strength setu masīvs
const getStrengthSets = (logExercise: any) => {
    const reps    = logExercise.reps_completed || [];
    const weights = logExercise.weights_used   || [];
    return Array.from({ length: logExercise.sets_completed }, (_, i) => ({
        reps:   reps[i]    || 0,
        weight: Array.isArray(weights[i]) ? (weights[i]?.weight || 0) : (weights[i] || 0)
    }));
};

// Kardio setu masīvs — laiki sekundēs
const getCardioSets = (logExercise: any) => {
    const durations = logExercise.durations_completed || [];
    return Array.from({ length: logExercise.sets_completed }, (_, i) => ({
        duration_seconds: durations[i] || 0
    }));
};

// Muskuļu grupas joslas platums procentos
const muscleGroupPercent = (count: number) => {
    const total = Object.values(props.stats.muscle_groups).reduce((s: number, v: any) => s + v, 0);
    return total > 0 ? Math.round((count / total) * 100) : 0;
};

// Vai vispār ir kāds strength vingrinājums
const hasStrength = props.workoutLog?.log_exercises?.some((ex: any) => !ex.is_cardio);

// Vai vispār ir kāds kardio vingrinājums
const hasCardio = props.workoutLog?.log_exercises?.some((ex: any) => ex.is_cardio);

const deleteWorkout = () => {
    router.delete(route('workout-logs.destroy', props.workoutLog.id));
    showDeleteModal.value = false;
};
</script>

<template>
    <AppLayout>
        <div class="page">

            <!-- Galvene ar atpakaļ pogu -->
            <div class="topbar">
                <div class="topbar-left">
                    <Link :href="route('workout-logs.index')" class="back-link">← Atpakaļ</Link>
                    <div>
                        <h1 class="topbar-title">{{ workoutLog.name }}</h1>
                        <div class="topbar-meta">
                            <span>📅 {{ formatDate(workoutLog.completed_at) }}</span>
                            <span>⏱️ {{ workoutLog.duration_minutes }} min</span>
                            <span v-if="workoutLog.routine" class="routine-tag">{{ workoutLog.routine.name }}</span>
                        </div>
                    </div>
                </div>
                <button @click="showDeleteModal = true" class="btn-delete">🗑️ Dzēst</button>
            </div>

            <!-- Ātrie statistikas lodziņi — strength -->
            <div v-if="hasStrength" class="quick-stats">
                <div class="qstat">
                    <div class="qstat-val">{{ stats.total_sets }}</div>
                    <div class="qstat-lbl">Kopējie seti</div>
                </div>
                <div class="qstat">
                    <div class="qstat-val">{{ stats.total_reps }}</div>
                    <div class="qstat-lbl">Atkārtojumi</div>
                </div>
                <div class="qstat">
                    <div class="qstat-val">{{ stats.total_weight }} kg</div>
                    <div class="qstat-lbl">Kopējais svars</div>
                </div>
                <div class="qstat">
                    <div class="qstat-val">{{ stats.average_weight_per_set }} kg</div>
                    <div class="qstat-lbl">Vid. svars/setam</div>
                </div>
            </div>

            <!-- Kardio statistika — rāda tikai ja ir kardio -->
            <div v-if="hasCardio" class="cardio-stats">
                <div class="cardio-stat">
                    <div class="cardio-val">{{ stats.total_sets }}</div>
                    <div class="cardio-lbl">Kopējie seti</div>
                </div>
                <div class="cardio-stat">
                    <div class="cardio-val">{{ stats.total_cardio_formatted || '—' }}</div>
                    <div class="cardio-lbl">Kopējais kardio laiks</div>
                </div>
            </div>

            <!-- Saturs: vingrinājumi + sānjosla -->
            <div class="content">

                <div class="exercises-col">
                    <div class="section-card">
                        <div class="section-header">
                            <h2 class="section-title">Vingrinājumi</h2>
                            <span class="count-badge">{{ workoutLog.log_exercises?.length || 0 }}</span>
                        </div>

                        <div class="exercise-list">
                            <div v-for="ex in workoutLog.log_exercises" :key="ex.id" class="exercise-card"
                                 :class="{ 'exercise-card-cardio': isCardio(ex) }">

                                <div class="ex-header">
                                    <div class="ex-header-info">
                                        <div class="ex-name">{{ ex.exercise?.name }}</div>
                                        <div class="ex-meta">
                                            <span v-if="ex.exercise?.muscle_group" class="muscle-badge">
                                                {{ ex.exercise.muscle_group }}
                                            </span>
                                            <span v-if="isCardio(ex)" class="cardio-badge">Kardio</span>
                                        </div>
                                    </div>
                                    <div class="ex-done">
                                        <div class="ex-done-num">{{ ex.sets_completed }}</div>
                                        <div class="ex-done-lbl">seti</div>
                                    </div>
                                </div>

                                <!-- Strength seti -->
                                <template v-if="!isCardio(ex)">
                                    <div class="sets-grid">
                                        <div v-for="(set, i) in getStrengthSets(ex)" :key="i" class="set-box">
                                            <div class="set-num-lbl">Set {{ i + 1 }}</div>
                                            <div class="set-reps">{{ set.reps }}×</div>
                                            <div class="set-weight">{{ set.weight }} kg</div>
                                        </div>
                                        <div v-if="getStrengthSets(ex).length === 0" class="no-data">Nav datu</div>
                                    </div>
                                </template>

                                <!-- Kardio seti ar laiku -->
                                <template v-else>
                                    <div class="sets-grid-cardio">
                                        <div v-for="(set, i) in getCardioSets(ex)" :key="i" class="set-box-cardio">
                                            <div class="set-num-lbl">Set {{ i + 1 }}</div>
                                            <div class="set-duration">{{ formatDuration(set.duration_seconds) }}</div>
                                            <div class="set-duration-lbl">laiks</div>
                                        </div>
                                        <div v-if="getCardioSets(ex).length === 0" class="no-data">Nav datu</div>
                                    </div>
                                </template>

                                <div v-if="ex.notes" class="ex-notes">
                                    <span class="notes-label">Piezīmes:</span> {{ ex.notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sānjosla -->
                <div class="sidebar">

                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Muskuļu grupas</h3>
                        <div class="muscle-list">
                            <div v-for="(count, mg) in stats.muscle_groups" :key="mg" class="muscle-row">
                                <div class="muscle-name">{{ mg }}</div>
                                <div class="muscle-bar-wrap">
                                    <div class="muscle-bar">
                                        <div class="muscle-fill" :style="{ width: muscleGroupPercent(count) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="muscle-count">{{ count }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Papildu informācija</h3>
                        <div class="info-list">
                            <!-- Strength papildus info -->
                            <template v-if="hasStrength">
                                <div class="info-row">
                                    <span class="info-lbl">Vid. atkārtojumi/setā</span>
                                    <span class="info-val">{{ stats.average_reps_per_set }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Kopējais svars</span>
                                    <span class="info-val">{{ stats.total_weight }} kg</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-lbl">Vid. svars/setā</span>
                                    <span class="info-val">{{ stats.average_weight_per_set }} kg</span>
                                </div>
                            </template>
                            <!-- Kardio papildus info -->
                            <template v-if="hasCardio">
                                <div class="info-row">
                                    <span class="info-lbl">Kopējais kardio laiks</span>
                                    <span class="info-val cardio-accent">{{ stats.total_cardio_formatted || '—' }}</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div v-if="similarWorkouts.length > 0" class="sidebar-card">
                        <h3 class="sidebar-title">Līdzīgi treniņi</h3>
                        <div class="similar-list">
                            <div v-for="s in similarWorkouts" :key="s.id" class="similar-row">
                                <div>
                                    <div class="similar-name">{{ s.name }}</div>
                                    <div class="similar-date">{{ formatDate(s.completed_at) }}</div>
                                </div>
                                <Link :href="route('workout-logs.show', s.id)" class="similar-link">Skatīt →</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dzēšanas apstiprinājums -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal">
                    <div class="modal-header">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <h3>Dzēst treniņu?</h3>
                    </div>
                    <div class="modal-body">
                        <p>Vai tiešām vēlaties dzēst šo treniņu?</p>
                        <p class="modal-warn">Šī darbība nevar tikt atcelta.</p>
                    </div>
                    <div class="modal-footer">
                        <button @click="showDeleteModal = false" class="btn-cancel">Atcelt</button>
                        <button @click="deleteWorkout" class="btn-confirm">Dzēst</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
    .page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem 2rem;
        min-height: 100vh;
        background: #f3f4f6;
    }

    .topbar {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        margin: 0 -1rem 1.5rem;
        background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
        box-shadow: 0 2px 12px rgba(230,92,0,0.3);
    }

    .topbar-left {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .back-link {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: color 0.15s;
    }

        .back-link:hover {
            color: white;
        }

    .topbar-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.35rem;
    }

    .topbar-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.85);
    }

    .routine-tag {
        background: rgba(0,0,0,0.2);
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        font-weight: 500;
    }

    .btn-delete {
        padding: 0.45rem 1rem;
        background: rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 0.5rem;
        color: white;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.15s;
        flex-shrink: 0;
    }

        .btn-delete:hover {
            background: rgba(239,68,68,0.6);
        }

    /* Strength statistikas josla */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .qstat {
        background: white;
        border-radius: 0.875rem;
        padding: 1.1rem 1rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        text-align: center;
    }

    .qstat-val {
        font-size: 1.625rem;
        font-weight: 700;
        color: #ff8c42;
        margin-bottom: 0.25rem;
        line-height: 1;
    }

    .qstat-lbl {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
    }

    /* Kardio statistikas josla — zaļā tēma */
    .cardio-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .cardio-stat {
        background: white;
        border-radius: 0.875rem;
        padding: 1.1rem 1rem;
        border: 1px solid #a7f3d0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        text-align: center;
        background: linear-gradient(135deg, #f0fdf4, white);
    }

    .cardio-val {
        font-size: 1.625rem;
        font-weight: 700;
        color: #059669;
        margin-bottom: 0.25rem;
        line-height: 1;
    }

    .cardio-lbl {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
    }

    .cardio-accent {
        color: #059669;
        font-weight: 700;
    }

    .content {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.25rem;
        align-items: start;
    }

    .section-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }

    .count-badge {
        background: #ff8c42;
        color: white;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }

    .exercise-list {
        display: flex;
        flex-direction: column;
    }

    .exercise-card {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
    }

        .exercise-card:last-child {
            border-bottom: none;
        }

    /* Kardio kartiņa ar zaļu akcentu */
    .exercise-card-cardio {
        background: linear-gradient(to right, #f0fdf4, white);
        border-left: 3px solid #a7f3d0;
    }

    .ex-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.875rem;
    }

    .ex-header-info {
        flex: 1;
    }

    .ex-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.35rem;
    }

    .ex-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .muscle-badge {
        font-size: 0.7rem;
        font-weight: 600;
        color: #e65c00;
        background: #fff7ed;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        border: 1px solid #fed7aa;
    }

    .cardio-badge {
        font-size: 0.7rem;
        font-weight: 600;
        color: #059669;
        background: #d1fae5;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        border: 1px solid #a7f3d0;
    }

    .ex-done {
        text-align: right;
        flex-shrink: 0;
    }

    .ex-done-num {
        font-size: 1.375rem;
        font-weight: 700;
        color: #ff8c42;
        line-height: 1;
    }

    .ex-done-lbl {
        font-size: 0.7rem;
        color: #9ca3af;
    }

    .sets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 0.5rem;
    }

    .set-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem;
        text-align: center;
    }

    .set-num-lbl {
        font-size: 0.65rem;
        color: #9ca3af;
        margin-bottom: 0.25rem;
    }

    .set-reps {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }

    .set-weight {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.15rem;
    }

    .sets-grid-cardio {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 0.5rem;
    }

    .set-box-cardio {
        background: #f0fdf4;
        border: 1px solid #a7f3d0;
        border-radius: 0.5rem;
        padding: 0.5rem;
        text-align: center;
    }

    .set-duration {
        font-size: 1.1rem;
        font-weight: 700;
        color: #059669;
        line-height: 1;
        margin-top: 0.15rem;
    }

    .set-duration-lbl {
        font-size: 0.65rem;
        color: #6b7280;
        margin-top: 0.15rem;
    }

    .no-data {
        font-size: 0.8rem;
        color: #9ca3af;
        font-style: italic;
        padding: 0.5rem;
    }

    .ex-notes {
        margin-top: 0.75rem;
        padding: 0.6rem 0.75rem;
        background: #fffbeb;
        border-radius: 0.5rem;
        border: 1px solid #fde68a;
        font-size: 0.8rem;
        color: #6b7280;
        line-height: 1.5;
    }

    .notes-label {
        font-weight: 600;
        color: #d97706;
        margin-right: 0.25rem;
    }

    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .sidebar-card {
        background: white;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .sidebar-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.875rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f3f4f6;
    }

    .muscle-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .muscle-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .muscle-name {
        font-size: 0.775rem;
        color: #374151;
        width: 90px;
        flex-shrink: 0;
    }

    .muscle-bar-wrap {
        flex: 1;
    }

    .muscle-bar {
        height: 5px;
        background: #e5e7eb;
        border-radius: 9999px;
        overflow: hidden;
    }

    .muscle-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff8c42, #e65c00);
        border-radius: 9999px;
    }

    .muscle-count {
        font-size: 0.75rem;
        font-weight: 600;
        color: #374151;
        width: 30px;
        text-align: right;
    }

    .info-list {
        display: flex;
        flex-direction: column;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.8rem;
    }

        .info-row:last-child {
            border-bottom: none;
        }

    .info-lbl {
        color: #6b7280;
    }

    .info-val {
        font-weight: 600;
        color: #111827;
    }

    .similar-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .similar-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        transition: border-color 0.15s;
    }

        .similar-row:hover {
            border-color: #ff8c42;
        }

    .similar-name {
        font-size: 0.8rem;
        font-weight: 500;
        color: #111827;
        margin-bottom: 0.15rem;
    }

    .similar-date {
        font-size: 0.72rem;
        color: #9ca3af;
    }

    .similar-link {
        font-size: 0.775rem;
        color: #ff8c42;
        text-decoration: none;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }

        .similar-link:hover {
            color: #e65c00;
        }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
    }

    .modal {
        background: white;
        border-radius: 1rem;
        max-width: 24rem;
        width: 100%;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.1rem 1.25rem;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

        .modal-header h3 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

    .modal-body {
        padding: 1.1rem 1.25rem;
    }

        .modal-body p {
            color: #4b5563;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 0.35rem;
        }

    .modal-warn {
        color: #dc2626;
        font-weight: 500;
        font-size: 0.8rem !important;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.625rem;
        padding: 0.875rem 1.25rem 1.1rem;
    }

    .btn-cancel {
        padding: 0.45rem 1.1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: white;
        color: #6b7280;
        font-weight: 500;
        cursor: pointer;
        font-size: 0.875rem;
        transition: background 0.15s;
    }

        .btn-cancel:hover {
            background: #f3f4f6;
        }

    .btn-confirm {
        padding: 0.45rem 1.1rem;
        border: none;
        border-radius: 0.5rem;
        background: #ef4444;
        color: white;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.875rem;
        transition: background 0.15s;
    }

        .btn-confirm:hover {
            background: #dc2626;
        }

    /* Mobilais skats */
    @media (max-width: 480px) {
        .topbar {
            padding: 1rem;
            flex-wrap: wrap;
        }

        .topbar-title {
            font-size: 1.1rem;
        }

        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.625rem;
        }

        .cardio-stats {
            grid-template-columns: 1fr;
        }

        .qstat-val, .cardio-val {
            font-size: 1.375rem;
        }

        .content {
            grid-template-columns: 1fr;
        }

        .sets-grid, .sets-grid-cardio {
            grid-template-columns: repeat(auto-fill, minmax(75px, 1fr));
        }

        .muscle-name {
            width: 75px;
            font-size: 0.72rem;
        }
    }
</style>
