<template>
    <AppLayout>
        <Head title="My Routines" />

        <div class="routines-page">
            <div class="routines-container">
                <div class="routines-content">
                    <div class="routines-header">
                        <h1>Mana rutīnas</h1>
                        <div class="header-actions">
                            <Link href="/routines/create" class="create-button">
                            Izveidot jaunu rutīnu
                            </Link>
                        </div>
                    </div>

                    <!-- Aktīvās rutīnas indikators -->
                    <div v-if="activeRoutine" class="active-routine-banner">
                        <div class="active-routine-content">
                            <div class="routine-info">
                                <h3>🏋️ Aktīvā rutīna</h3>
                                <h4>{{ activeRoutine.name }}</h4>
                                <div class="routine-details">
                                    <span class="exercise-count">{{ activeRoutine.exercises_count || 0 }} vingrinājumi</span>
                                    <span v-if="activeRoutine.is_public" class="public-tag">Publiska</span>
                                </div>
                            </div>
                            <button @click="clearActiveRoutine" class="remove-active-btn">
                                Noņemt
                            </button>
                        </div>
                    </div>

                    <div class="routines-list">
                        <div class="routines-grid">
                            <div v-for="routine in routines" :key="routine.id"
                                 :class="['routine-card', routine.id === activeRoutine?.id ? 'active-routine' : '']">
                                <div class="routine-content">
                                    <div class="routine-header">
                                        <h2>{{ routine.name }}</h2>
                                        <div v-if="routine.id === activeRoutine?.id" class="active-badge">
                                            Aktīvā
                                        </div>
                                    </div>
                                    <p class="routine-description">{{ routine.description || 'Nav apraksta' }}</p>

                                    <div class="routine-meta">
                                        <span class="exercise-count">{{ routine.exercises.length }} vingrinājumi</span>
                                        <span v-if="routine.is_public" class="public-tag">Publiska</span>
                                    </div>

                                    <div class="routine-actions">
                                        <button @click="viewRoutineDetails(routine)" class="view-btn">
                                            👁️ Skatīt
                                        </button>
                                        <button @click="startRoutineWorkout(routine)" class="start-btn">
                                            ▶️ Sākt treniņu
                                        </button>
                                        <button @click="setAsActiveRoutine(routine)"
                                                :class="['set-active-btn', routine.id === activeRoutine?.id ? 'is-active' : '']">
                                            {{ routine.id === activeRoutine?.id ? '✅ Aktīvā' : '⭐ Iestatīt' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="routines.length === 0" class="empty-state">
                                <div class="empty-icon">
                                    🏋️
                                </div>
                                <p>Jums vēl nav izveidota neviena rutīna.</p>
                                <Link href="/routines/create" class="create-button">
                                Izveidot pirmo rutīnu
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rutīnas detaļu modals -->
            <div v-if="selectedRoutine" class="modal-overlay" @click.self="closeModal">
                <div class="modal">
                    <div class="modal-header">
                        <h2>{{ selectedRoutine.name }}</h2>
                        <button @click="closeModal" class="modal-close">
                            ✕
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="modal-section">
                            <h3>📝 Apraksts</h3>
                            <p>{{ selectedRoutine.description || 'Nav apraksta' }}</p>
                        </div>

                        <div class="modal-section">
                            <h3>📊 Informācija</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Vingrinājumi:</span>
                                    <span class="info-value">{{ selectedRoutine.exercises.length }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Statuss:</span>
                                    <span :class="['info-value', selectedRoutine.is_public ? 'public' : 'private']">
                                        {{ selectedRoutine.is_public ? 'Publiska' : 'Privāta' }}
                                    </span>
                                </div>
                                <div v-if="selectedRoutine.id === activeRoutine?.id" class="info-item">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value active">⭐ Aktīvā</span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section">
                            <h3>💪 Vingrinājumi ({{ selectedRoutine.exercises.length }})</h3>
                            <div v-if="selectedRoutine.exercises.length > 0" class="exercises-list">
                                <div v-for="(exercise, index) in selectedRoutine.exercises"
                                     :key="exercise.id || index"
                                     class="exercise-card">
                                    <div class="exercise-header">
                                        <div class="exercise-number">{{ index + 1 }}</div>
                                        <div class="exercise-title">
                                            <h4>{{ exercise.name }}</h4>
                                            <span class="muscle-badge">{{ exercise.muscle_group }}</span>
                                        </div>
                                    </div>

                                    <div class="exercise-specs">
                                        <div class="spec-item">
                                            <span class="spec-label">Seti:</span>
                                            <span class="spec-value">{{ exercise.pivot?.sets || 3 }}</span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Reps:</span>
                                            <span class="spec-value">{{ exercise.pivot?.reps || 10 }}</span>
                                        </div>
                                        <div v-if="exercise.pivot?.weight" class="spec-item">
                                            <span class="spec-label">Svars:</span>
                                            <span class="spec-value">{{ exercise.pivot.weight }}kg</span>
                                        </div>
                                        <div v-if="exercise.pivot?.rest_time" class="spec-item">
                                            <span class="spec-label">Atpūta:</span>
                                            <span class="spec-value">{{ exercise.pivot.rest_time }}s</span>
                                        </div>
                                    </div>

                                    <div v-if="exercise.pivot?.notes" class="exercise-notes">
                                        <span class="notes-label">Piezīmes:</span>
                                        <p>{{ exercise.pivot.notes }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="no-exercises">
                                🏋️ Šai rutīnai vēl nav vingrinājumu
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="startRoutineWorkout(selectedRoutine)" class="btn btn-primary">
                            ▶️ Sākt treniņu
                        </button>
                        <button @click="setAsActiveRoutine(selectedRoutine)"
                                :class="['btn', selectedRoutine.id === activeRoutine?.id ? 'btn-success' : 'btn-secondary']">
                            {{ selectedRoutine.id === activeRoutine?.id ? '✅ Aktīvā' : '⭐ Iestatīt kā aktīvo' }}
                        </button>
                        <button @click="closeModal" class="btn btn-outline">
                            Aizvērt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, onMounted, onUnmounted } from 'vue';

    const props = defineProps({
        routines: Array,
    });

    const activeRoutine = ref(null);
    const selectedRoutine = ref(null);

    onMounted(() => {
        const saved = localStorage.getItem('activeRoutine');
        if (saved) {
            activeRoutine.value = JSON.parse(saved);
        }

        document.addEventListener('keydown', handleEscape);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleEscape);
    });

    const setAsActiveRoutine = (routine) => {
        const data = {
            id: routine.id,
            name: routine.name,
            description: routine.description,
            exercises_count: routine.exercises.length,
            is_public: routine.is_public,
            exercises: routine.exercises.map(ex => ({
                id: ex.id,
                name: ex.name,
                muscle_group: ex.muscle_group,
                sets: ex.pivot?.sets || 3,
                reps: ex.pivot?.reps || 10,
                weight: ex.pivot?.weight || 0,
                rest_time: ex.pivot?.rest_time || 60,
                notes: ex.pivot?.notes || ''
            }))
        };

        localStorage.setItem('activeRoutine', JSON.stringify(data));
        activeRoutine.value = data;
        localStorage.setItem('routineChanged', 'true');

        if (selectedRoutine.value?.id === routine.id) {
            closeModal();
        }
    };

    const clearActiveRoutine = () => {
        localStorage.removeItem('activeRoutine');
        activeRoutine.value = null;
        localStorage.setItem('routineChanged', 'true');
    };

    const viewRoutineDetails = (routine) => {
        selectedRoutine.value = routine;
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        selectedRoutine.value = null;
        document.body.style.overflow = 'auto';
    };

    const startRoutineWorkout = (routine) => {
        const data = {
            id: routine.id,
            name: routine.name,
            exercises: routine.exercises.map(ex => ({
                id: ex.id,
                name: ex.name,
                muscle_group: ex.muscle_group,
                sets: ex.pivot?.sets || 3,
                reps: ex.pivot?.reps || 10,
                weight: ex.pivot?.weight || 0,
                rest_time: ex.pivot?.rest_time || 60,
                notes: ex.pivot?.notes || ''
            }))
        };

        localStorage.setItem('activeRoutineForWorkout', JSON.stringify(data));

        router.visit('/workout/free', {
            method: 'get',
            data: {
                routine_id: routine.id,
                routine_name: routine.name
            }
        });
    };

    const handleEscape = (e) => {
        if (e.key === 'Escape' && selectedRoutine.value) {
            closeModal();
        }
    };
</script>

<style scoped>
    /* Base Styles */
    .routines-page {
        padding: 2rem 1rem;
        background: #f8fafc;
        min-height: 100vh;
    }

    .routines-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .routines-content {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        padding: 2rem;
    }

    /* Header */
    .routines-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

        .routines-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

    .create-button {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

        .create-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.3);
        }

    /* Active Routine Banner */
    .active-routine-banner {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .active-routine-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .routine-info h3 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        opacity: 0.9;
    }

    .routine-info h4 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .routine-details {
        display: flex;
        gap: 1rem;
        align-items: center;
        font-size: 0.875rem;
    }

    .public-tag {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
    }

    .remove-active-btn {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

        .remove-active-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

    /* Grid */
    .routines-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    /* Routine Cards */
    .routine-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.5rem;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

        .routine-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }

    .active-routine {
        border-color: #3b82f6;
        background: linear-gradient(to bottom, #f0f9ff, white);
        position: relative;
    }

        .active-routine::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            border-radius: 1rem 1rem 0 0;
        }

    .routine-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .routine-card h2 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        flex: 1;
    }

    .active-badge {
        background: #10b981;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .routine-description {
        color: #64748b;
        font-size: 0.9375rem;
        line-height: 1.6;
        margin: 0 0 1.25rem 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .routine-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .exercise-count {
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .routine-meta .public-tag {
        background: #dcfce7;
        color: #166534;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* Buttons */
    .routine-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .view-btn,
    .start-btn,
    .set-active-btn {
        flex: 1;
        min-width: 100px;
        padding: 0.625rem 1rem;
        border-radius: 0.625rem;
        font-size: 0.875rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .view-btn {
        background: #f8fafc;
        color: #475569;
        border: 2px solid #e2e8f0;
    }

        .view-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

    .start-btn {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        color: white;
    }

        .start-btn:hover {
            background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%);
            transform: translateY(-1px);
        }

    .set-active-btn {
        background: #3b82f6;
        color: white;
    }

        .set-active-btn:hover {
            background: #2563eb;
        }

        .set-active-btn.is-active {
            background: #10b981;
        }

    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 4rem 2rem;
        color: #64748b;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-state p {
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
    }

    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .modal {
        background: white;
        border-radius: 1rem;
        width: 100%;
        max-width: 700px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        border-bottom: 2px solid #f1f5f9;
    }

        .modal-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.75rem;
        color: #94a3b8;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 0.375rem;
        line-height: 1;
        transition: all 0.2s;
    }

        .modal-close:hover {
            color: #64748b;
            background: #f1f5f9;
        }

    .modal-body {
        padding: 2rem;
    }

    .modal-section {
        margin-bottom: 2rem;
    }

        .modal-section h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-section p {
            color: #475569;
            line-height: 1.6;
            margin: 0;
        }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
    }

    .info-label {
        color: #64748b;
        font-weight: 500;
    }

    .info-value {
        font-weight: 600;
        color: #1e293b;
    }

        .info-value.public {
            color: #166534;
            background: #dcfce7;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .info-value.private {
            color: #475569;
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .info-value.active {
            color: #ea580c;
            background: #ffedd5;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

    /* Exercises List */
    .exercises-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .exercise-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.25rem;
    }

    .exercise-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .exercise-number {
        width: 2rem;
        height: 2rem;
        background: #3b82f6;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    .exercise-title {
        flex: 1;
    }

        .exercise-title h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 0.5rem 0;
        }

    .muscle-badge {
        background: #e2e8f0;
        color: #475569;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .exercise-specs {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .spec-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
    }

    .spec-label {
        color: #64748b;
        font-size: 0.875rem;
    }

    .spec-value {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.875rem;
    }

    .exercise-notes {
        padding: 0.75rem;
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        font-size: 0.875rem;
    }

    .notes-label {
        display: block;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.25rem;
        font-size: 0.75rem;
    }

    .exercise-notes p {
        color: #475569;
        margin: 0;
        line-height: 1.4;
    }

    .no-exercises {
        text-align: center;
        padding: 2rem;
        color: #94a3b8;
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 2px dashed #e2e8f0;
        font-size: 0.9375rem;
    }

    /* Modal Footer */
    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 2px solid #f1f5f9;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn {
        flex: 1;
        min-width: 120px;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-align: center;
    }

    .btn-primary {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
        color: white;
    }

        .btn-primary:hover {
            background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%);
            transform: translateY(-1px);
        }

    .btn-secondary {
        background: #3b82f6;
        color: white;
    }

        .btn-secondary:hover {
            background: #2563eb;
        }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-outline {
        background: transparent;
        color: #64748b;
        border: 2px solid #e2e8f0;
    }

        .btn-outline:hover {
            background: #f8fafc;
        }

    /* Scrollbar */
    .modal::-webkit-scrollbar {
        width: 6px;
    }

    .modal::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .modal::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

        .modal::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

    /* Responsive */
    @media (max-width: 768px) {
        .routines-content {
            padding: 1.5rem;
        }

        .routines-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .routines-grid {
            grid-template-columns: 1fr;
        }

        .active-routine-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .routine-actions {
            flex-direction: column;
        }

        .view-btn,
        .start-btn,
        .set-active-btn {
            width: 100%;
        }

        .modal {
            margin: 0.5rem;
            max-height: 90vh;
        }

        .modal-header,
        .modal-body {
            padding: 1.25rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .exercise-specs {
            grid-template-columns: 1fr;
        }

        .modal-footer {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .routines-page {
            padding: 1rem 0.5rem;
        }

        .routines-content {
            padding: 1rem;
        }

        .routine-card {
            padding: 1.25rem;
        }
    }
</style>
