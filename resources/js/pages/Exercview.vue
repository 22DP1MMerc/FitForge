<!-- resources/js/Pages/Exercview.vue -->

<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { useModal } from '@/Composables/useModal';
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

// Admins var pievienot/rediģēt/dzēst
const isAdmin = computed(() => !!props.auth?.user?.is_admin);

// Bildes kļūdu stāvoklis
const imageErrors = ref<Record<number, boolean>>({});
const handleImageError = (id: number) => { imageErrors.value[id] = true; };

// Unsplash bildes katrai muskuļu grupai kā rezerve
const groupImages: Record<string, string> = {
    'Krūtis':         'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80',
    'Mugura':         'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&q=80',
    'Kājas':          'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80',
    'Pleci':          'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&q=80',
    'Rokas':          'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Korsete':        'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=80',
    'Kardio':         'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=600&q=80',
    'Pilns ķermenis': 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
};

// Konkrētu vingrinājumu bildes
const exerciseImages: Record<string, string> = {
    'Bench Press':           'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80',
    'Incline Bench Press':   'https://images.unsplash.com/photo-1598971639058-fab3c3109a53?w=600&q=80',
    'Decline Bench Press':   'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80',
    'Dumbbell Press':        'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Chest Fly':             'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&q=80',
    'Cable Crossover':       'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'Push Ups':              'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=80',
    'Deadlift':              'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
    'Pull Ups':              'https://images.unsplash.com/photo-1598266663439-2056e6900339?w=600&q=80',
    'Chin Ups':              'https://images.unsplash.com/photo-1598266663439-2056e6900339?w=600&q=80',
    'Lat Pulldown':          'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'Barbell Row':           'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
    'Seated Cable Row':      'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'Single Arm Dumbbell Row':'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'T-Bar Row':             'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600&q=80',
    'Squat':                 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80',
    'Front Squat':           'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80',
    'Leg Press':             'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80',
    'Walking Lunge':         'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=600&q=80',
    'Bulgarian Split Squat': 'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=600&q=80',
    'Leg Curl':              'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80',
    'Leg Extension':         'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=600&q=80',
    'Calf Raise':            'https://images.unsplash.com/photo-1434682881908-b43d0467b798?w=600&q=80',
    'Overhead Press':        'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&q=80',
    'Arnold Press':          'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Lateral Raise':         'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&q=80',
    'Front Raise':           'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?w=600&q=80',
    'Face Pull':             'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'Upright Row':           'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
    'Barbell Curl':          'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Hammer Curl':           'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Preacher Curl':         'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Close Grip Bench Press':'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600&q=80',
    'Tricep Pushdown':       'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'Dips':                  'https://images.unsplash.com/photo-1598266663439-2056e6900339?w=600&q=80',
    'Skull Crushers':        'https://images.unsplash.com/photo-1598971639058-fab3c3109a53?w=600&q=80',
    'Plank':                 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=80',
    'Hanging Leg Raise':     'https://images.unsplash.com/photo-1598266663439-2056e6900339?w=600&q=80',
    'Russian Twist':         'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=600&q=80',
    'Cable Crunch':          'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=600&q=80',
    'Running':               'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=600&q=80',
    'Cycling':               'https://images.unsplash.com/photo-1534787238916-9ba6764efd4f?w=600&q=80',
    'Jump Rope':             'https://images.unsplash.com/photo-1601422407692-ec4eeec1d9b3?w=600&q=80',
    'Stair Climber':         'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=600&q=80',
    'Burpees':               'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
    'Clean and Press':       'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
    'Farmer Walk':           'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=600&q=80',
    'Kettlebell Swing':      'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80',
};

// Iegūst bildi — DB lauks > nosaukuma karte > muskuļu grupas karte
const getImageUrl = (exercise: any): string => {
    if (imageErrors.value[exercise.id]) {
        return groupImages[exercise.muscle_group]
            ?? 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80';
    }
    if (exercise.image_url && !exercise.image_url.includes('/images/defaults/')) {
        return exercise.image_url;
    }
    return exerciseImages[exercise.name]
        ?? groupImages[exercise.muscle_group]
        ?? 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80';
};

const formatWeight = (w: any): string => {
    const n = parseFloat(w);
    if (isNaN(n)) return '0';
    return n % 1 === 0 ? n.toString() : n.toFixed(1);
};

// Filtrēšana — atjaunina URL bez lapas pārlādes
watch([muscleGroupFilter, equipmentFilter], () => {
    const filters: Record<string, string> = {};
    if (muscleGroupFilter.value) filters.muscle_group = muscleGroupFilter.value;
    if (equipmentFilter.value)   filters.equipment    = equipmentFilter.value;
    router.get('exercises', filters, { preserveState: true, replace: true });
});

// Admin: dzēš ar custom apstiprinājumu
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

                    <!-- Galvene -->
                    <div class="page-header">
                        <div>
                            <h1>Vingrinājumu bibliotēka</h1>
                            <p class="subtitle">{{ exercises.length }} vingrinājumi</p>
                        </div>
                        <Link v-if="isAdmin" :href="route('exercises.create')" class="btn btn--primary">
                        + Pievienot vingrinājumu
                        </Link>
                    </div>

                    <!-- Filtri -->
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
                        <button v-if="muscleGroupFilter || equipmentFilter"
                                @click="muscleGroupFilter = ''; equipmentFilter = ''"
                                class="btn btn--outline filter-clear">
                            ✕ Notīrīt
                        </button>
                    </div>

                    <!-- Režģis -->
                    <div class="grid">
                        <div v-for="exercise in exercises" :key="exercise.id" class="ex-card">

                            <!-- Bilde -->
                            <div class="ex-img-wrap">
                                <img :src="getImageUrl(exercise)"
                                     :alt="exercise.name"
                                     class="ex-img"
                                     loading="lazy"
                                     @error="handleImageError(exercise.id)" />
                                <span v-if="exercise.difficulty" class="diff-badge">
                                    {{ exercise.difficulty }}
                                </span>
                                <!-- Admin pogas — parādās uz hover -->
                                <div v-if="isAdmin" class="admin-overlay">
                                    <Link :href="route('exercises.edit', exercise.id)"
                                          class="admin-btn admin-btn--edit">
                                    ✏️ Rediģēt
                                    </Link>
                                    <button @click="deleteExercise(exercise)"
                                            class="admin-btn admin-btn--delete">
                                        🗑️ Dzēst
                                    </button>
                                </div>
                            </div>

                            <!-- Saturs -->
                            <div class="ex-body">
                                <h2>{{ exercise.name }}</h2>
                                <p class="ex-desc">{{ exercise.description }}</p>

                                <!-- Personīgais rekords -->
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

                        <!-- Nav rezultātu -->
                        <div v-if="exercises.length === 0" class="empty">
                            <div class="empty__icon">🔍</div>
                            <p>Netika atrasti vingrinājumi pēc filtriem.</p>
                            <button @click="muscleGroupFilter = ''; equipmentFilter = ''"
                                    class="btn btn--outline">
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

    /* Bilde */
    .ex-img-wrap {
        position: relative;
        width: 100%;
        height: 190px;
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

    .diff-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
        color: #fff;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    /* Admin overlay */
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

    /* Karte saturs */
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

    /* PR josla */
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

    /* Tagetes */
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

    /* Tukšs stāvoklis */
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

    /* Pogas */
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

    /* Mobilais */
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

        .ex-img-wrap {
            height: 170px;
        }
        /* Mobilajā admin pogas rādās vienmēr */
        .admin-overlay {
            opacity: 1;
            background: rgba(0,0,0,0.45);
        }
    }
</style>
