<!-- resources/js/Pages/Routines/Edit.vue -->

<template>
    <AppLayout>
        <Head title="Rediģēt rutīnu" />

        <div class="edit-routine-page">
            <div class="container">
                <div class="header">
                    <h1>✏️ Rediģēt rutīnu</h1>
                    <Link :href="route('routines.my')" class="back-link">
                    ← Atpakaļ uz rutīnu sarakstu
                    </Link>
                </div>

                <div class="edit-form">
                    <form @submit.prevent="submitForm">
                        <!-- Pamatinformācijas sadaļa -->
                        <div class="form-section">
                            <h2>Pamatinformācija</h2>

                            <div class="form-group">
                                <label for="name">Rutīnas nosaukums *</label>
                                <input type="text"
                                       id="name"
                                       v-model="form.name"
                                       required
                                       class="form-input"
                                       :class="{ 'error': form.errors.name }" />
                                <div v-if="form.errors.name" class="error-message">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="description">Apraksts</label>
                                <textarea id="description"
                                          v-model="form.description"
                                          rows="3"
                                          class="form-textarea"
                                          :class="{ 'error': form.errors.description }"></textarea>
                                <div v-if="form.errors.description" class="error-message">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox"
                                           v-model="form.is_public"
                                           class="checkbox-input" />
                                    <span>Publiska rutīna (redzama citiem lietotājiem)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Vingrinājumu sadaļa -->
                        <div class="form-section">
                            <div class="section-header">
                                <h2>Vingrinājumi</h2>
                                <button type="button" @click="addExercise" class="add-btn">
                                    ➕ Pievienot vingrinājumu
                                </button>
                            </div>

                            <div v-if="form.exercises.length === 0" class="empty-exercises">
                                <p>Nav pievienotu vingrinājumu</p>
                            </div>

                            <div v-else class="exercises-list">
                                <div v-for="(exercise, index) in form.exercises"
                                     :key="index"
                                     class="exercise-item">

                                    <div class="exercise-header">
                                        <h3>Vingrinājums {{ index + 1 }}</h3>
                                        <button type="button"
                                                @click="removeExercise(index)"
                                                class="remove-btn">
                                            🗑️
                                        </button>
                                    </div>

                                    <div class="exercise-form">
                                        <!-- Vingrinājuma izvēle -->
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Vingrinājums *</label>
                                                <select v-model="exercise.id"
                                                        required
                                                        class="form-select"
                                                        @change="updateExerciseDetails(index)">
                                                    <option value="">Izvēlieties vingrinājumu</option>
                                                    <option v-for="ex in availableExercises"
                                                            :key="ex.id"
                                                            :value="ex.id">
                                                        {{ ex.name }} ({{ ex.muscle_group }})
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Diena *</label>
                                                <select v-model="exercise.day_number"
                                                        required
                                                        class="form-select">
                                                    <option v-for="day in weekDays"
                                                            :key="day.id"
                                                            :value="day.id">
                                                        {{ day.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Seti un reps -->
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Seti *</label>
                                                <input type="number"
                                                       v-model="exercise.sets"
                                                       min="1"
                                                       max="10"
                                                       required
                                                       class="form-input number-input">
                                            </div>

                                            <div class="form-group">
                                                <label>Reps *</label>
                                                <input type="number"
                                                       v-model="exercise.reps"
                                                       min="1"
                                                       max="50"
                                                       required
                                                       class="form-input number-input">
                                            </div>

                                            <div class="form-group">
                                                <label>Atpūta (sekundes)</label>
                                                <input type="number"
                                                       v-model="exercise.rest_seconds"
                                                       min="0"
                                                       max="300"
                                                       class="form-input number-input">
                                            </div>
                                        </div>

                                        <!-- Piezīmes -->
                                        <div class="form-group">
                                            <label>Piezīmes</label>
                                            <textarea v-model="exercise.notes"
                                                      rows="2"
                                                      class="form-textarea"></textarea>
                                        </div>

                                        <!-- Iepriekšējā vingrinājuma informācija -->
                                        <div v-if="selectedExerciseDetails[index]"
                                             class="exercise-info">
                                            <p><strong>Muskuļu grupa:</strong> {{ selectedExerciseDetails[index].muscle_group }}</p>
                                            <p v-if="selectedExerciseDetails[index].description">
                                                <strong>Apraksts:</strong> {{ selectedExerciseDetails[index].description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formas pogas -->
                        <div class="form-actions">
                            <button type="submit"
                                    :disabled="form.processing"
                                    class="submit-btn">
                                <span v-if="form.processing">Saglabā...</span>
                                <span v-else>💾 Saglabāt izmaiņas</span>
                            </button>

                            <Link :href="route('routines.index')"
                                  class="cancel-btn">
                            ❌ Atcelt
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import { ref, computed, onMounted } from 'vue';

    const props = defineProps({
        routine: Object,
        exercises: Array,
        weekDays: Array,
    });

    // Forma - IMPORTANT: Change exercise_id to id to match controller validation
    const form = useForm({
        name: props.routine.name,
        description: props.routine.description || '',
        is_public: props.routine.is_public || false,
        exercises: props.routine.exercises ? props.routine.exercises.map(ex => ({
            id: ex.id, // Changed from exercise_id to id
            name: ex.name,
            muscle_group: ex.muscle_group,
            day_number: ex.pivot?.day_number || 1,
            sets: ex.pivot?.sets || 3,
            reps: ex.pivot?.reps || 10,
            rest_seconds: ex.pivot?.rest_seconds || 60,
            notes: ex.pivot?.notes || ''
        })) : []
    });

    // Iepriekš izvēlēto vingrinājumu detaļas
    const selectedExerciseDetails = ref({});
    const availableExercises = ref(props.exercises || []);

    // Atjaunina vingrinājuma detaļas
    const updateExerciseDetails = (index) => {
        const exerciseId = form.exercises[index].id; // Changed from exercise_id to id
        const exercise = availableExercises.value.find(ex => ex.id == exerciseId);

        if (exercise) {
            selectedExerciseDetails.value[index] = {
                name: exercise.name,
                muscle_group: exercise.muscle_group,
                description: exercise.description
            };
        }
    };

    // Pievieno jaunu vingrinājumu
    const addExercise = () => {
        form.exercises.push({
            id: '', // Changed from exercise_id to id
            day_number: 1,
            sets: 3,
            reps: 10,
            rest_seconds: 60,
            notes: ''
        });
    };

    // Noņem vingrinājumu
    const removeExercise = (index) => {
        if (confirm('Vai tiešām vēlaties dzēst šo vingrinājumu?')) {
            form.exercises.splice(index, 1);
            delete selectedExerciseDetails.value[index];
        }
    };

    // Iesniedz formu - FIXED: Prepare data properly for controller
    const submitForm = () => {
        // Prepare exercises data to match controller validation rules
        const formData = {
            ...form.data(),
            exercises: form.exercises.map(ex => ({
                id: ex.id, // Controller expects 'id' not 'exercise_id'
                day_number: ex.day_number,
                sets: ex.sets,
                reps: ex.reps,
                rest_seconds: ex.rest_seconds,
                notes: ex.notes
            }))
        };

        form.put(route('routines.update', { routine: props.routine.id }), {
            data: formData,
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show success message
                alert('Rutīna veiksmīgi atjaunināta!');
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            }
        });
    };

    // Inicializējam izvēlētos vingrinājumus
    onMounted(() => {
        form.exercises.forEach((_, index) => {
            updateExerciseDetails(index);
        });
    });
</script>

<style scoped>
    .edit-routine-page {
        padding: 2rem 1rem;
        background: #f8fafc;
        min-height: 100vh;
    }

    .container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

    .back-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

        .back-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

    .edit-form {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .form-section {
        margin-bottom: 3rem;
    }

        .form-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .add-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

        .add-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

    .form-group {
        margin-bottom: 1.5rem;
    }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.error, .form-textarea.error {
            border-color: #dc2626;
        }

    .error-message {
        color: #dc2626;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .checkbox-input {
        margin-right: 0.5rem;
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .number-input {
        text-align: center;
    }

    .empty-exercises {
        text-align: center;
        padding: 3rem;
        background: #f8fafc;
        border-radius: 0.5rem;
        border: 2px dashed #d1d5db;
        color: #6b7280;
    }

    .exercises-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .exercise-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.5rem;
    }

    .exercise-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }

        .exercise-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

    .remove-btn {
        background: #ef4444;
        color: white;
        border: none;
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

        .remove-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

    .exercise-form {
        padding: 0.5rem;
    }

    .exercise-info {
        margin-top: 1rem;
        padding: 1rem;
        background: white;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
    }

        .exercise-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }

    .submit-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

    .cancel-btn {
        background: #6b7280;
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

        .cancel-btn:hover {
            background: #4b5563;
        }

    /* Responsive */
    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .add-btn {
            width: 100%;
        }

        .form-actions {
            flex-direction: column;
        }

        .submit-btn, .cancel-btn {
            width: 100%;
        }
    }
</style>
