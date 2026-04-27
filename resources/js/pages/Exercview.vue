<!-- resources/js/Pages/Exercview.vue -->

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Modal from '@/components/Modal.vue';
import { useModal } from '@/composables/useModal';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const { modalRef, confirm, success, error } = useModal();

const props = defineProps<{
    exercises:        any[];
    muscleGroups:     string[];
    equipmentOptions: string[];
    filters:          { muscle_group?: string; equipment?: string };
    auth:             { user?: any } | null;
}>();

const muscleGroupFilter = ref(props.filters.muscle_group || '');
const equipmentFilter   = ref(props.filters.equipment   || '');

// Meklēšana notiek lokāli — bez servera pieprasījuma
const searchQuery = ref('');

const isAdmin = computed(() => !!props.auth?.user?.is_admin);

// Filtrē lokāli pēc nosaukuma vai muskuļu grupas
const filteredExercises = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return props.exercises;
    return props.exercises.filter(ex =>
        ex.name.toLowerCase().includes(q) ||
        ex.muscle_group.toLowerCase().includes(q) ||
        ex.equipment?.toLowerCase().includes(q)
    );
});

const imageErrors = ref<Record<number, boolean>>({});
const handleImageError = (id: number) => { imageErrors.value[id] = true; };

const fallbackImages: Record<string, string> = {
    'Krūtis':         'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&q=70',
    'Mugura':         'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&q=70',
    'Kājas':          'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=400&q=70',
    'Pleci':          'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=400&q=70',
    'Rokas':          'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=400&q=70',
    'Korsete':        'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=400&q=70',
    'Kardio':         'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=400&q=70',
    'Pilns ķermenis': 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&q=70',
};

const getImageUrl = (exercise: any): string => {
    if (imageErrors.value[exercise.id]) {
        return fallbackImages[exercise.muscle_group]
            ?? 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&q=70';
    }
    const url: string = exercise.image_url ?? '';
    if (url && !url.includes('/images/defaults/')) return url;
    return fallbackImages[exercise.muscle_group]
        ?? 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&q=70';
};

const formatWeight = (w: any): string => {
    const n = parseFloat(w);
    if (isNaN(n)) return '0';
    return n % 1 === 0 ? n.toString() : n.toFixed(1);
};

// Filtri uz serveri ar debounce
let filterTimer: ReturnType<typeof setTimeout>;
watch([muscleGroupFilter, equipmentFilter], () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        const filters: Record<string, string> = {};
        if (muscleGroupFilter.value) filters.muscle_group = muscleGroupFilter.value;
        if (equipmentFilter.value)   filters.equipment    = equipmentFilter.value;
        router.get('exercises', filters, { preserveState: true, replace: true });
    }, 400);
});

const clearAll = () => {
    searchQuery.value      = '';
    muscleGroupFilter.value = '';
    equipmentFilter.value   = '';
};

const deleteExercise = async (exercise: any) => {
    const confirmed = await confirm({
        title:       'Dzēst vingrinājumu?',
        message:     `Vai tiešām dzēst "${exercise.name}"? Tas tiks noņemts no visām rutīnām.`,
        confirmText: 'Dzēst',
        cancelText:  'Atcelt',
    });
    if (!confirmed) return;

    router.delete(route('exercises.destroy', { exercise: exercise.id }), {
        onSuccess: async () => await success(`"${exercise.name}" dzēsts!`),
        onError:   async () => await error('Kļūda dzēšot vingrinājumu.'),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Vingrinājumi" />

        <div class="page">
            <div class="container">
                <div class="card">

                    <!-- Virsraksts -->
                    <div class="page-header">
                        <div>
                            <h1>Vingrinājumu bibliotēka</h1>
                            <p class="subtitle">{{ filteredExercises.length }} vingrinājumi</p>
                        </div>
                        <Link v-if="isAdmin" :href="route('exercises.create')" class="btn btn--primary">
                        + Pievienot vingrinājumu
                        </Link>
                    </div>

                    <!-- Meklēšana un filtri -->
                    <div class="search-row">
                        <div class="search-wrap">
                            <span class="search-icon">🔍</span>
                            <input v-model="searchQuery"
                                   type="text"
                                   class="search-input"
                                   placeholder="Meklēt pēc nosaukuma, muskuļu grupas..." />
                            <button v-if="searchQuery"
                                    @click="searchQuery = ''"
                                    class="search-clear">
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="filters">
                        <div class="filter-group">
                            <label>Muskuļu grupa</label>
                            <select v-model="muscleGroupFilter" class="select">
                                <option value="">Visas muskuļu grupas</option>
                                <option v-for="g in muscleGroups" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Aprīkojums</label>
                            <select v-model="equipmentFilter" class="select">
                                <option value="">Viss aprīkojums</option>
                                <option v-for="e in equipmentOptions" :key="e" :value="e">{{ e }}</option>
                            </select>
                        </div>
                        <button v-if="muscleGroupFilter || equipmentFilter || searchQuery"
                                @click="clearAll"
                                class="btn btn--outline filter-clear">
                            ✕ Notīrīt
                        </button>
                    </div>

                    <!-- Kartiņu režģis -->
                    <div class="grid">
                        <div v-for="exercise in filteredExercises" :key="exercise.id" class="ex-card">

                            <div class="ex-img-wrap">
                                <img :src="getImageUrl(exercise)"
                                     :alt="exercise.name"
                                     class="ex-img"
                                     loading="lazy"
                                     @error="handleImageError(exercise.id)" />
                                <div v-if="isAdmin" class="admin-overlay">
                                    <Link :href="route('exercises.edit', exercise.id)"
                                          class="admin-btn admin-btn--edit">✏️ Rediģēt</Link>
                                    <button @click="deleteExercise(exercise)"
                                            class="admin-btn admin-btn--delete">
                                        🗑️ Dzēst
                                    </button>
                                </div>
                            </div>

                            <div class="ex-body">
                                <h2>{{ exercise.name }}</h2>
                                <p class="ex-desc">{{ exercise.description }}</p>

                                <div v-if="exercise.personal_records?.length" class="pr-box">
                                    <span class="pr-label">PR</span>
                                    <span class="pr-value">
                                        {{ formatWeight(exercise.personal_records[0].weight) }}kg
                                        × {{ exercise.personal_records[0].reps }}
                                    </span>
                                </div>

                                <div class="ex-tags">
                                    <span class="tag tag--dark">{{ exercise.muscle_group }}</span>
                                    <span class="tag tag--orange">{{ exercise.equipment }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Nekas neatbilst -->
                        <div v-if="filteredExercises.length === 0" class="empty">
                            <div class="empty__icon">🔍</div>
                            <p>Netika atrasts neviens vingrinājums.</p>
                            <button @click="clearAll" class="btn btn--outline">
                                Notīrīt filtrus
                            </button>
                        </div>
                    </div>

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
        max-width: 1200px;
        margin: 0 auto;
    }

    .card {
        background: #fff;
        border-radius: 0.875rem;
        padding: 2rem;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
    }

    /* Galvene */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.75rem;
        padding-bottom: 1.25rem;
        border-bottom: 2px solid #f3f4f6;
    }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.25rem;
        }

    .subtitle {
        font-size: 0.875rem;
        color: #9ca3af;
        margin: 0;
    }

    /* Meklēšanas josla */
    .search-row {
        margin-bottom: 1rem;
    }

    .search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-icon {
        position: absolute;
        left: 0.875rem;
        font-size: 0.95rem;
        pointer-events: none;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 2.5rem 0.75rem 2.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.625rem;
        font-size: 0.95rem;
        color: #111827;
        background: #fff;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

        .search-input:focus {
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.15);
        }

        .search-input::placeholder {
            color: #9ca3af;
        }

    /* X poga meklēšanas notīrīšanai */
    .search-clear {
        position: absolute;
        right: 0.75rem;
        background: none;
        border: none;
        color: #9ca3af;
        font-size: 0.9rem;
        cursor: pointer;
        padding: 0.25rem;
        line-height: 1;
        border-radius: 50%;
        transition: color 0.15s, background 0.15s;
    }

        .search-clear:hover {
            color: #374151;
            background: #f3f4f6;
        }

    /* Filtri */
    .filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 180px;
    }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

    .select {
        width: 100%;
        padding: 0.65rem 0.875rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        color: #111827;
        background: #fff;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

        .select:focus {
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.15);
        }

    .filter-clear {
        align-self: flex-end;
        white-space: nowrap;
    }

    /* Režģis */
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
        gap: 1.25rem;
    }

    /* Karte */
    .ex-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.875rem;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

        .ex-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

    .ex-img-wrap {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        overflow: hidden;
        background: #f3f4f6;
        flex-shrink: 0;
    }

    .ex-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .ex-card:hover .ex-img {
        transform: scale(1.06);
    }

    .admin-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .ex-card:hover .admin-overlay {
        opacity: 1;
    }

    .admin-btn {
        padding: 0.45rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        text-decoration: none;
        transition: all 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .admin-btn--edit {
        background: #fff;
        color: #111827;
    }

        .admin-btn--edit:hover {
            background: #f3f4f6;
        }

    .admin-btn--delete {
        background: #dc2626;
        color: #fff;
    }

        .admin-btn--delete:hover {
            background: #b91c1c;
        }

    .ex-body {
        padding: 1.125rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

        .ex-body h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.5rem;
        }

    .ex-desc {
        font-size: 0.85rem;
        color: #6b7280;
        line-height: 1.5;
        margin: 0 0 0.875rem;
        flex: 1;
    }

    .pr-box {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 0.5rem;
        padding: 0.4rem 0.75rem;
        margin-bottom: 0.875rem;
    }

    .pr-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .pr-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: #78350f;
    }

    .ex-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .tag {
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .tag--dark {
        background: #111827;
        color: #fff;
    }

    .tag--orange {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }

    .empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3.5rem 1rem;
    }

    .empty__icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }

    .empty p {
        font-size: 0.95rem;
        color: #6b7280;
        margin-bottom: 1.25rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.6rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .btn--primary {
        background: #ff8c42;
        color: #fff;
    }

        .btn--primary:hover {
            background: #e65c00;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(230,92,0,0.3);
        }

    .btn--outline {
        background: #fff;
        color: #374151;
        border: 1px solid #d1d5db;
    }

        .btn--outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

    @media (max-width: 768px) {
        .card {
            padding: 1.25rem;
        }

        .page-header {
            flex-direction: column;
            gap: 1rem;
        }

        .filters {
            flex-direction: column;
        }

        .filter-group {
            min-width: unset;
            width: 100%;
        }

        .grid {
            grid-template-columns: 1fr;
        }

        .admin-overlay {
            opacity: 1;
            background: rgba(0,0,0,0.45);
        }
    }
</style>
