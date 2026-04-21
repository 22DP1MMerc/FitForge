<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    exercises: { type: Array,  required: true, default: () => [] },
    weekDays:  { type: Array,  required: true, default: () => [] }
});

// Atiet atpakaļ uz rutīnu sarakstu
const cancel = () => router.get(route('routines.index'));

// Formas sākuma stāvoklis — 7 dienas, tukšas
const form = ref({
    name:        '',
    description: '',
    is_public:   false,
    days: [
        { day_number: 1, day_name: 'Pirmdiena',  exercises: [] },
        { day_number: 2, day_name: 'Otrdiena',   exercises: [] },
        { day_number: 3, day_name: 'Trešdiena',  exercises: [] },
        { day_number: 4, day_name: 'Ceturtdiena', exercises: [] },
        { day_number: 5, day_name: 'Piektdiena', exercises: [] },
        { day_number: 6, day_name: 'Sestdiena',  exercises: [] },
        { day_number: 7, day_name: 'Svētdiena',  exercises: [] },
    ]
});

// Pievieno tukšu vingrinājuma rindu konkrētai dienai
const addExerciseToDay = (day) => {
    day.exercises.push({ id: null, sets: 3, reps: 10, rest_seconds: 60, notes: '' });
};

// Noņem vingrinājumu no dienas
const removeExerciseFromDay = (day, index) => {
    day.exercises.splice(index, 1);
};

// Sagatavo datus un sūta uz serveri
const submit = () => {
    const exercises = form.value.days.flatMap(day =>
        day.exercises
            .filter(ex => ex.id)
            .map(ex => ({
                id:           ex.id,
                day_number:   day.day_number,
                sets:         ex.sets,
                reps:         ex.reps,
                rest_seconds: ex.rest_seconds,
                notes:        ex.notes
            }))
    );

    router.post(route('routines.store'), {
        name:        form.value.name,
        description: form.value.description,
        is_public:   form.value.is_public,
        exercises
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Izveidot rutīnu" />

        <div class="create-routine-page">
            <div class="container">

                <div class="page-header">
                    <div class="header-badge">
                        <span class="badge-icon">💪</span>
                    </div>
                    <h1 class="page-title">Izveidot jaunu rutīnu</h1>
                    <p class="page-subtitle">Izveido savu treniņu plānu</p>
                </div>

                <form @submit.prevent="submit" class="routine-form">

                    <!-- Pamatinformācija -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">📋</div>
                            <h2 class="section-title">Rutīnas informācija</h2>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="routine-name" class="form-label">
                                    Rutīnas nosaukums <span class="required-star">*</span>
                                </label>
                                <input id="routine-name" v-model="form.name" type="text"
                                       class="form-input"
                                       placeholder="piem., Pilna ķermeņa spēka treniņš" required />
                            </div>

                            <div class="form-group full-width">
                                <label for="routine-description" class="form-label">Apraksts</label>
                                <textarea id="routine-description" v-model="form.description"
                                          rows="4" class="form-textarea"
                                          placeholder="Apraksti savus treniņa mērķus..."></textarea>
                            </div>

                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" v-model="form.is_public" class="checkbox-input" />
                                    <span class="checkbox-text">Padarīt šo rutīnu publisku</span>
                                    <span class="checkbox-hint">Citi lietotāji varēs redzēt un izmantot šo rutīnu</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Nedēļas plāns — katrai dienai sava kartiņa -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">📅</div>
                            <h2 class="section-title">Nedēļas plāns</h2>
                        </div>

                        <div class="days-grid">
                            <div class="day-card" v-for="day in form.days" :key="day.day_number">
                                <div class="day-header">
                                    <h3 class="day-name">{{ day.day_name }}</h3>
                                    <button type="button" @click="addExerciseToDay(day)" class="btn-add-exercise">
                                        <span class="btn-icon">+</span>
                                        Pievienot
                                    </button>
                                </div>

                                <div class="exercises-list">
                                    <div v-for="(exercise, index) in day.exercises" :key="index" class="exercise-item">
                                        <div class="exercise-header">
                                            <span class="exercise-number">#{{ index + 1 }}</span>
                                            <button type="button" @click="removeExerciseFromDay(day, index)" class="btn-remove">
                                                🗑️ Dzēst
                                            </button>
                                        </div>

                                        <div class="exercise-field">
                                            <label class="exercise-label">Vingrinājums</label>
                                            <select v-model="exercise.id" class="exercise-select" required>
                                                <option :value="null">Izvēlēties vingrinājumu</option>
                                                <option v-for="ex in exercises" :key="ex.id" :value="ex.id">
                                                    {{ ex.name }} ({{ ex.muscle_group }})
                                                </option>
                                            </select>
                                        </div>

                                        <div class="exercise-stats-grid">
                                            <div class="stat-field">
                                                <label class="exercise-label">Seti</label>
                                                <input type="number" v-model.number="exercise.sets"
                                                       min="1" class="stat-input" required />
                                            </div>
                                            <div class="stat-field">
                                                <label class="exercise-label">Atkārtojumi</label>
                                                <input type="number" v-model.number="exercise.reps"
                                                       min="1" class="stat-input" required />
                                            </div>
                                            <div class="stat-field">
                                                <label class="exercise-label">Atpūta (sek.)</label>
                                                <input type="number" v-model.number="exercise.rest_seconds"
                                                       min="0" class="stat-input" />
                                            </div>
                                        </div>

                                        <div class="exercise-field">
                                            <label class="exercise-label">Piezīmes</label>
                                            <textarea v-model="exercise.notes" rows="2"
                                                      class="exercise-textarea"
                                                      placeholder="Padomi, tehnika vai variācijas..."></textarea>
                                        </div>
                                    </div>

                                    <div v-if="day.exercises.length === 0" class="empty-day">
                                        <p class="empty-message">Nav pievienotu vingrinājumu</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="cancel" class="btn-cancel">Atcelt</button>
                        <button type="submit" class="btn-submit">
                            <span class="btn-submit-icon">✓</span>
                            Saglabāt rutīnu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .create-routine-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
        padding: 2rem 1rem;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }

    .header-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #f97316, #ea580c);
        border-radius: 50%;
        margin-bottom: 1rem;
        box-shadow: 0 10px 25px rgba(249,115,22,0.3);
    }

    .badge-icon {
        font-size: 2.5rem;
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #1e293b, #334155);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        font-size: 1.125rem;
        color: #64748b;
    }

    .routine-form {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .form-section {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        overflow: hidden;
        transition: box-shadow 0.2s;
    }

        .form-section:hover {
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-bottom: 2px solid #f97316;
    }

    .section-icon {
        font-size: 1.75rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .form-grid {
        padding: 2rem;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

        .form-group.full-width {
            grid-column: span 2;
        }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .required-star {
        color: #ef4444;
    }

    .form-input, .form-textarea {
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: all 0.2s;
        font-family: inherit;
    }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }

    .form-textarea {
        resize: vertical;
    }

    .checkbox-group {
        grid-column: span 2;
    }

    .checkbox-label {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        cursor: pointer;
    }

    .checkbox-input {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        accent-color: #f97316;
    }

    .checkbox-text {
        font-weight: 600;
        color: #1e293b;
    }

    .checkbox-hint {
        font-size: 0.875rem;
        color: #64748b;
    }

    .days-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 1.5rem;
        padding: 2rem;
    }

    .day-card {
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.2s;
    }

        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

    .day-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        background: white;
        border-bottom: 2px solid #f97316;
    }

    .day-name {
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .btn-add-exercise {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-add-exercise:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(249,115,22,0.3);
        }

    .btn-icon {
        font-size: 1.125rem;
        font-weight: bold;
    }

    .exercises-list {
        padding: 1rem;
        max-height: 600px;
        overflow-y: auto;
    }

    .exercise-item {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

        .exercise-item:hover {
            border-color: #f97316;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

    .exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .exercise-number {
        font-size: 0.75rem;
        font-weight: 600;
        color: #f97316;
        background: #fef3c7;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }

    .btn-remove {
        padding: 0.25rem 0.75rem;
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-remove:hover {
            background: #fecaca;
        }

    .exercise-field {
        margin-bottom: 1rem;
    }

    .exercise-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .exercise-select, .stat-input, .exercise-textarea {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.2s;
        font-family: inherit;
    }

        .exercise-select:focus, .stat-input:focus, .exercise-textarea:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 2px rgba(249,115,22,0.1);
        }

    .exercise-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .stat-field {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .exercise-textarea {
        resize: vertical;
    }

    .empty-day {
        text-align: center;
        padding: 2rem;
        color: #94a3b8;
    }

    .empty-message {
        font-size: 0.875rem;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        padding: 1.5rem 2rem;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    .btn-cancel, .btn-submit {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
    }

        .btn-cancel:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

    .btn-submit {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249,115,22,0.4);
        }

    .btn-submit-icon {
        font-size: 1.125rem;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
            padding: 1.5rem;
        }

        .form-group.full-width, .checkbox-group {
            grid-column: span 1;
        }

        .days-grid {
            grid-template-columns: 1fr;
            padding: 1.5rem;
        }

        .exercise-stats-grid {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-cancel, .btn-submit {
            width: 100%;
            justify-content: center;
        }

        .page-title {
            font-size: 1.75rem;
        }

        .section-title {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 480px) {
        .day-header {
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-add-exercise {
            width: 100%;
            justify-content: center;
        }
    }
</style>
