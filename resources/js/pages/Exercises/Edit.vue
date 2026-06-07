<!-- resources/js/Pages/Exercises/Edit.vue -->

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Modal from '@/components/Modal.vue';
import { useModal } from '@/composables/useModal';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const { modalRef, confirm } = useModal();

const props = defineProps<{
    exercise:         any;
    muscleGroups:     string[];
    equipmentOptions: string[];
    types:            string[];
}>();

const form = useForm({
    name:         props.exercise.name,
    description:  props.exercise.description  || '',
    muscle_group: props.exercise.muscle_group || '',
    equipment:    props.exercise.equipment    || '',
    type:         props.exercise.type         || 'strength',
    image:        null as File | null,
    remove_image: false,
});

const customMuscleGroup = ref(!props.muscleGroups.includes(props.exercise.muscle_group));
const customEquipment   = ref(!props.equipmentOptions.includes(props.exercise.equipment));

const fileInputRef = ref<HTMLInputElement | null>(null);
const imagePreview = ref<string | null>(props.exercise.image_url || null);

const handleFileSelect = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const clearImage = () => {
    form.image = null;
    form.remove_image = true;
    imagePreview.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const submitForm = () => {
    form.put(route('exercises.update', { exercise: props.exercise.id }), {
        preserveScroll: true,
    });
};

// Dzēš ar apstiprinājumu
const deleteExercise = async () => {
    const confirmed = await confirm({
        title:       'Dzēst vingrinājumu?',
        message:     `Vai tiešām dzēst "${props.exercise.name}"? To nevar atcelt.`,
        confirmText: 'Dzēst',
        cancelText:  'Atcelt',
    });
    if (!confirmed) return;

    router.delete(route('exercises.destroy', { exercise: props.exercise.id }));
};
</script>

<template>
    <AppLayout>
        <Head :title="`Rediģēt: ${exercise.name}`" />

        <div class="page">
            <div class="container">

                <div class="page-header">
                    <h1>✏️ Rediģēt vingrinājumu</h1>
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
                                       :class="{ 'input--error': form.errors.name }" />
                                <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
                            </div>

                            <div class="form-group">
                                <label>Apraksts</label>
                                <textarea v-model="form.description"
                                          rows="3"
                                          class="input"
                                          :class="{ 'input--error': form.errors.description }"></textarea>
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
                                        <button type="button" @click="customMuscleGroup = true" class="toggle-btn">
                                            + Jauna
                                        </button>
                                    </div>
                                    <div v-else class="input-with-toggle">
                                        <input v-model="form.muscle_group" type="text" required
                                               class="input" placeholder="Ievadi muskuļu grupu" />
                                        <button type="button" @click="customMuscleGroup = false" class="toggle-btn">
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
                                        <button type="button" @click="customEquipment = true" class="toggle-btn">
                                            + Jauns
                                        </button>
                                    </div>
                                    <div v-else class="input-with-toggle">
                                        <input v-model="form.equipment" type="text" required
                                               class="input" placeholder="Ievadi aprīkojumu" />
                                        <button type="button" @click="customEquipment = false" class="toggle-btn">
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
                                <label>Attēls (JPG, PNG, WebP — max 4 MB)</label>
                                <div class="file-upload-area" :class="{ 'has-image': imagePreview }"
                                     @click="fileInputRef?.click()">
                                    <img v-if="imagePreview" :src="imagePreview" class="upload-preview" alt="Priekšskatījums" />
                                    <div v-else class="upload-placeholder">
                                        <svg width="32" height="32" fill="none" stroke="#9ca3af" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                        </svg>
                                        <span>Klikšķināt, lai izvēlētos attēlu</span>
                                        <span class="upload-hint">vai ievelciet failu šeit</span>
                                    </div>
                                    <button v-if="imagePreview" type="button" class="clear-img-btn" @click.stop="clearImage">×</button>
                                </div>
                                <input ref="fileInputRef" type="file" accept="image/*"
                                       class="hidden-file-input" @change="handleFileSelect" />
                                <p v-if="form.errors.image" class="err">{{ form.errors.image }}</p>
                            </div>
                        </section>

                        <!-- Pogas -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary" :disabled="form.processing">
                                <span v-if="form.processing">Saglabā...</span>
                                <span v-else>💾 Saglabāt</span>
                            </button>
                            <Link :href="route('exercises.index')" class="btn btn--outline">Atcelt</Link>
                            <button type="button" @click="deleteExercise" class="btn btn--danger">
                                🗑️ Dzēst
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <Modal ref="modalRef" />
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

    .file-upload-area {
        position: relative;
        border: 2px dashed #d1d5db;
        border-radius: 0.75rem;
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
        background: #f9fafb;
        transition: border-color 0.2s, background 0.2s;
    }

    .file-upload-area:hover {
        border-color: #ff8c42;
        background: #fff7f0;
    }

    .file-upload-area.has-image {
        border-style: solid;
        border-color: #e5e7eb;
    }

    .upload-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
        font-size: 0.875rem;
        pointer-events: none;
    }

    .upload-hint {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .clear-img-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 50%;
        background: rgba(0,0,0,0.55);
        color: white;
        border: none;
        font-size: 1rem;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }

    .clear-img-btn:hover { background: rgba(0,0,0,0.8); }

    .hidden-file-input {
        display: none;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f3f4f6;
        align-items: center;
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

    /* Dzēšanas poga labajā pusē */
    .btn--danger {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
        margin-left: auto;
    }

        .btn--danger:hover {
            background: #dc2626;
            color: #fff;
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

        .btn--danger {
            margin-left: 0;
        }
    }
</style>
