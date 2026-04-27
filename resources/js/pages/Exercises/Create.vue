<!-- resources/js/Pages/Exercises/Create.vue -->

<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    muscleGroups:     string[];
    equipmentOptions: string[];
    types:            string[];
}>();

// Tikai lauki kas ir DB — bez difficulty un instructions
const form = useForm({
    name:         '',
    description:  '',
    muscle_group: '',
    equipment:    '',
    type:         'strength',
    image:        '',
});

// Pielāgots ievads ja sarakstā nav vajadzīgā opcija
const customMuscleGroup = ref(false);
const customEquipment   = ref(false);

const submitForm = () => {
    form.post(route('exercises.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Pievienot vingrinājumu" />

        <div class="page">
            <div class="container">

                <div class="page-header">
                    <h1>➕ Pievienot vingrinājumu</h1>
                    <Link :href="route('exercises.index')" class="back-link">← Atpakaļ</Link>
                </div>

                <div class="card">
                    <form @submit.prevent="submitForm">

                        <section class="section">
                            <h2>Pamatinformācija</h2>

                            <div class="form-group">
                                <label>Nosaukums *</label>
                                <input v-model="form.name"
                                       type="text"
                                       required
                                       class="input"
                                       :class="{ 'input--error': form.errors.name }"
                                       placeholder="piem., Bench Press" />
                                <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
                            </div>

                            <div class="form-group">
                                <label>Apraksts</label>
                                <textarea v-model="form.description"
                                          rows="3"
                                          class="input"
                                          :class="{ 'input--error': form.errors.description }"
                                          placeholder="Īss vingrinājuma apraksts"></textarea>
                                <p v-if="form.errors.description" class="err">{{ form.errors.description }}</p>
                            </div>

                            <div class="form-row">
                                <!-- Muskuļu grupa -->
                                <div class="form-group">
                                    <label>Muskuļu grupa *</label>
                                    <div v-if="!customMuscleGroup" class="input-with-toggle">
                                        <select v-model="form.muscle_group" required class="input"
                                                :class="{ 'input--error': form.errors.muscle_group }">
                                            <option value="">Izvēlies grupu</option>
                                            <option v-for="g in muscleGroups" :key="g" :value="g">{{ g }}</option>
                                        </select>
                                        <button type="button"
                                                @click="customMuscleGroup = true; form.muscle_group = ''"
                                                class="toggle-btn">
                                            + Jauna
                                        </button>
                                    </div>
                                    <div v-else class="input-with-toggle">
                                        <input v-model="form.muscle_group" type="text" required
                                               class="input" placeholder="Ievadi muskuļu grupu" />
                                        <button type="button"
                                                @click="customMuscleGroup = false; form.muscle_group = ''"
                                                class="toggle-btn">
                                            Saraksts
                                        </button>
                                    </div>
                                    <p v-if="form.errors.muscle_group" class="err">{{ form.errors.muscle_group }}</p>
                                </div>

                                <!-- Aprīkojums -->
                                <div class="form-group">
                                    <label>Aprīkojums *</label>
                                    <div v-if="!customEquipment" class="input-with-toggle">
                                        <select v-model="form.equipment" required class="input"
                                                :class="{ 'input--error': form.errors.equipment }">
                                            <option value="">Izvēlies aprīkojumu</option>
                                            <option v-for="e in equipmentOptions" :key="e" :value="e">{{ e }}</option>
                                        </select>
                                        <button type="button"
                                                @click="customEquipment = true; form.equipment = ''"
                                                class="toggle-btn">
                                            + Jauns
                                        </button>
                                    </div>
                                    <div v-else class="input-with-toggle">
                                        <input v-model="form.equipment" type="text" required
                                               class="input" placeholder="Ievadi aprīkojumu" />
                                        <button type="button"
                                                @click="customEquipment = false; form.equipment = ''"
                                                class="toggle-btn">
                                            Saraksts
                                        </button>
                                    </div>
                                    <p v-if="form.errors.equipment" class="err">{{ form.errors.equipment }}</p>
                                </div>
                            </div>

                            <!-- Tips -->
                            <div class="form-group">
                                <label>Tips</label>
                                <select v-model="form.type" class="input">
                                    <option value="strength">Spēka</option>
                                    <option value="cardio">Kardio</option>
                                </select>
                            </div>
                        </section>

                        <!-- Bilde -->
                        <section class="section">
                            <h2>Bilde</h2>

                            <div class="form-group">
                                <label>Bildes URL</label>
                                <input v-model="form.image"
                                       type="url"
                                       class="input"
                                       :class="{ 'input--error': form.errors.image }"
                                       placeholder="https://..." />
                                <p v-if="form.errors.image" class="err">{{ form.errors.image }}</p>
                                <!-- Reāllaika priekšskatījums -->
                                <div v-if="form.image" class="img-preview">
                                    <img :src="form.image" alt="Priekšskatījums" />
                                </div>
                            </div>
                        </section>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary" :disabled="form.processing">
                                <span v-if="form.processing">Saglabā...</span>
                                <span v-else>💾 Pievienot vingrinājumu</span>
                            </button>
                            <Link :href="route('exercises.index')" class="btn btn--outline">Atcelt</Link>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    .page {
        padding: 2rem 1rem;
        background: #f3f4f6;
        min-height: 100vh;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.75rem;
    }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

    .back-link {
        color: #ff8c42;
        font-weight: 600;
        text-decoration: none;
        padding: 0.5rem 1.25rem;
        border: 2px solid #ff8c42;
        border-radius: 0.625rem;
        transition: background 0.2s, color 0.2s;
    }

        .back-link:hover {
            background: #ff8c42;
            color: #fff;
        }

    .card {
        background: #fff;
        border-radius: 1rem;
        padding: 2rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }

    .section {
        margin-bottom: 2.5rem;
    }

        .section h2 {
            font-size: 0.85rem;
            font-weight: 700;
            color: #ff8c42;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f3f4f6;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

    .form-group {
        margin-bottom: 1.25rem;
    }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .input {
        width: 100%;
        padding: 0.7rem 0.875rem;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        color: #111827;
        font-size: 0.9rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

        .input:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.15);
        }

    .input--error {
        border-color: #ef4444;
    }

    .err {
        color: #ef4444;
        font-size: 0.78rem;
        margin-top: 0.25rem;
    }

    .input-with-toggle {
        display: flex;
        gap: 0.5rem;
    }

        .input-with-toggle .input {
            flex: 1;
        }

    .toggle-btn {
        padding: 0 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        background: #f9fafb;
        color: #374151;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s;
    }

        .toggle-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

    .img-preview {
        margin-top: 0.75rem;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        height: 180px;
        background: #f3f4f6;
    }

        .img-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1.5rem;
        border-top: 1px solid #f3f4f6;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.65rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn--primary {
        background: #ff8c42;
        color: #fff;
    }

        .btn--primary:hover:not(:disabled) {
            background: #e65c00;
            transform: translateY(-1px);
        }

        .btn--primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

    .btn--outline {
        background: #fff;
        color: #374151;
        border: 1px solid #d1d5db;
    }

        .btn--outline:hover {
            background: #f9fafb;
        }

    @media (max-width: 640px) {
        .card {
            padding: 1.25rem;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

            .form-actions .btn {
                width: 100%;
            }
    }
</style>
