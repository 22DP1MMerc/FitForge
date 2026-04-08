<script setup>
    import AppLayout from '@/layouts/AppLayout.vue'
    import { Head, Link, router } from '@inertiajs/vue3';
    import { ref, watch, computed } from 'vue';

    const props = defineProps({
        exercises: Array,
        muscleGroups: Array,
        equipmentOptions: Array,
        filters: Object,
    });

    const muscleGroupFilter = ref(props.filters.muscle_group || '');
    const equipmentFilter = ref(props.filters.equipment || '');

    // Image loading state
    const imageErrors = ref({});

    const formatWeight = (weight) => {
        if (!weight && weight !== 0) return '0';

        const num = parseFloat(weight);
        if (isNaN(num)) return '0';

        if (num % 1 === 0) {
            return num.toString();
        }

        return num.toFixed(1);
    };

    // Handle image loading errors
    const handleImageError = (exerciseId) => {
        imageErrors.value[exerciseId] = true;
    };

    // Get display image URL (fallback to placeholder if error)
    const getImageUrl = (exercise) => {
        if (imageErrors.value[exercise.id]) {
            return '/images/defaults/exercise-placeholder.jpg';
        }
        return exercise.image_url || '/images/defaults/exercise-placeholder.jpg';
    };

    watch([muscleGroupFilter, equipmentFilter], () => {
        const filters = {};

        if (muscleGroupFilter.value) {
            filters.muscle_group = muscleGroupFilter.value;
        }

        if (equipmentFilter.value) {
            filters.equipment = equipmentFilter.value;
        }

        router.get('exercises', filters, {
            preserveState: true,
            replace: true,
        });
    }, { immediate: true });
</script>

<template>
    <AppLayout>
        <Head title="Vingrinājumi" />

        <div class="exercises-page">
            <div class="exercises-container">
                <div class="exercises-content">
                    <div class="exercises-header">
                        <h1>Vingrinājumu bibliotēka</h1>

                        <div class="filters-container">
                            <div class="filter-group">
                                <label>Muskuļu grupa</label>
                                <select v-model="muscleGroupFilter">
                                    <option value="">Visas muskuļu grupas</option>
                                    <option v-for="group in muscleGroups" :key="group" :value="group">
                                        {{ group }}
                                    </option>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label>Aprīkojums</label>
                                <select v-model="equipmentFilter">
                                    <option value="">Viss aprīkojums</option>
                                    <option v-for="equipment in equipmentOptions" :key="equipment" :value="equipment">
                                        {{ equipment }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="exercises-grid">
                        <div v-for="exercise in exercises" :key="exercise.id" class="exercise-card">
                            <!-- Exercise Image -->
                            <div class="exercise-image-container">
                                <img :src="getImageUrl(exercise)"
                                     :alt="exercise.name"
                                     class="exercise-image"
                                     @error="handleImageError(exercise.id)"
                                     loading="lazy" />
                                <div class="difficulty-badge" v-if="exercise.difficulty">
                                    {{ exercise.difficulty }}
                                </div>
                            </div>

                            <h2>{{ exercise.name }}</h2>
                            <p class="exercise-description">{{ exercise.description }}</p>

                            <!-- Personal Record Section -->
                            <div v-if="exercise.personal_records && exercise.personal_records.length > 0"
                                 class="personal-record-simple">
                                <span class="pr-label">PR: </span>
                                <span class="pr-value">
                                    {{ formatWeight(exercise.personal_records[0].weight) }}kg
                                    <span class="pr-times">×</span>
                                    {{ exercise.personal_records[0].reps }}
                                </span>
                            </div>

                            <div class="exercise-tags">
                                <span class="muscle-tag">{{ exercise.muscle_group }}</span>
                                <span class="equipment-tag">{{ exercise.equipment }}</span>
                            </div>
                        </div>

                        <div v-if="exercises.length === 0" class="empty-state">
                            <div class="empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p>Netika atrasti vingrinājumi pēc jūsu filtriem.</p>
                            <button @click="muscleGroupFilter = ''; equipmentFilter = ''" class="clear-filters">
                                Notīrīt visus filtrus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    .exercises-page {
        padding: 3rem 0;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .exercises-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .exercises-content {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
        overflow: hidden;
        padding: 2rem;
    }

    .exercises-header {
        text-align: center;
        margin-bottom: 2rem;
    }

        .exercises-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
        }

    .filters-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        max-width: 800px;
        margin: 0 auto;
    }

    @media (min-width: 768px) {
        .filters-container {
            flex-direction: row;
        }
    }

    .filter-group {
        flex: 1;
    }

        .filter-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .filter-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            background-color: white;
            transition: all 0.2s ease;
        }

            .filter-group select:focus {
                outline: none;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.2);
            }

    .exercises-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }

    .exercise-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        overflow: hidden;
        transition: all 0.2s ease;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

        .exercise-card:hover {
            transform: translateY(-0.25rem);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

    /* Image Container Styles */
    .exercise-image-container {
        position: relative;
        width: 100%;
        height: 200px;
        overflow: hidden;
        background-color: #f3f4f6;
    }

    .exercise-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .exercise-card:hover .exercise-image {
        transform: scale(1.05);
    }

    .difficulty-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(4px);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: capitalize;
        z-index: 1;
    }

    .exercise-card h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        margin: 1rem 1rem 0.75rem 1rem;
    }

    .exercise-description {
        color: #6b7280;
        font-size: 0.875rem;
        margin: 0 1rem 1rem 1rem;
        line-height: 1.5;
        flex-grow: 1;
    }

    .personal-record-simple {
        margin: 0 1rem 1rem 1rem;
        padding: 0.5rem;
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-radius: 0.5rem;
        text-align: center;
    }

    .pr-label {
        font-weight: 600;
        color: #92400e;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .pr-value {
        font-weight: 700;
        color: #78350f;
        font-size: 1rem;
    }

    .pr-times {
        font-weight: 600;
        margin: 0 0.25rem;
    }

    .exercise-tags {
        display: flex;
        gap: 0.5rem;
        margin: 0 1rem 1rem 1rem;
    }

    .muscle-tag {
        background: #111827;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .equipment-tag {
        background: #ffedd5;
        color: #9a3412;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1rem;
        color: #6b7280;
    }

    .empty-icon {
        margin: 0 auto 1rem;
        width: 3rem;
        height: 3rem;
        color: #d1d5db;
    }

    .empty-state p {
        margin-bottom: 1.5rem;
    }

    .clear-filters {
        display: inline-block;
        padding: 0.5rem 1rem;
        color: #ea580c;
        font-weight: 500;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

        .clear-filters:hover {
            color: #9a3412;
            text-decoration: underline;
        }

    @media (max-width: 768px) {
        .exercises-content {
            padding: 1rem;
        }

        .exercises-grid {
            grid-template-columns: 1fr;
        }

        .exercise-image-container {
            height: 180px;
        }
    }
</style>
