<!-- resources/js/Pages/Routines/RoutineView.vue -->

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Modal from '@/components/Modal.vue';
import { useModal } from '@/composables/useModal';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

const { modalRef, confirm, success, error } = useModal();

// Props — auth satur arī is_admin
const props = defineProps<{
    routines: any[];
    auth:     { user?: any } | null;
}>();

const activeRoutine   = ref<any>(null);
const selectedRoutine = ref<any>(null);

const isAuthenticated = computed(() => !!props.auth?.user);

// Pārbauda vai lietotājs ir admins
const isAdmin = computed(() => !!props.auth?.user?.is_admin);

const dayNames: Record<number, string> = {
    1: 'Pirmdiena', 2: 'Otrdiena',   3: 'Trešdiena',
    4: 'Ceturtdiena', 5: 'Piektdiena', 6: 'Sestdiena', 7: 'Svētdiena',
};

const getDayName = (n: number) => dayNames[n] ?? `Diena ${n}`;

const getExercisesForDay = (routine: any, day: number) =>
    (routine.exercises ?? []).filter((e: any) => e.pivot?.day_number === day);

const getExercisesCountForDay = (routine: any, day: number) =>
    getExercisesForDay(routine, day).length;

const getTotalExercisesCount = (routine: any) =>
    new Set((routine.exercises ?? []).map((e: any) => e.id)).size;

// Savas rutīnas augšā, pārējās apakšā — abas grupas pēc datuma
const sortedRoutines = computed(() => {
    if (!props.auth?.user) return props.routines;
    const uid  = props.auth.user.id;
    const mine = props.routines.filter(r => r.user_id === uid);
    const rest = props.routines.filter(r => r.user_id !== uid);
    return [...mine, ...rest];
});

// Vai rādīt šķirtāju starp savām un svešajām
const firstOtherIndex = computed(() => {
    if (!props.auth?.user) return -1;
    const uid = props.auth.user.id;
    return sortedRoutines.value.findIndex(r => r.user_id !== uid);
});

// Admins var dzēst visu, parasts lietotājs — tikai savas
const canDelete = (routine: any) =>
    isAdmin.value || (isAuthenticated.value && props.auth?.user?.id === routine.user_id);

// Rediģēt var tikai īpašnieks (admins arī)
const canEdit = (routine: any) =>
    isAdmin.value || (isAuthenticated.value && props.auth?.user?.id === routine.user_id);

const editRoutine = (routine: any) => {
    if (!isAuthenticated.value) return;
    closeModal();
    router.visit(`/routines/${routine.id}/edit`);
};

// Dzēšana ar custom modālu
const deleteRoutine = async (routine: any) => {
    if (!canDelete(routine)) return;

    const confirmed = await confirm({
        title:       'Dzēst rutīnu?',
        message:     `Vai tiešām dzēst rutīnu "${routine.name}"?`,
        details:     { 'Vingrinājumi': getTotalExercisesCount(routine) },
        confirmText: 'Dzēst',
        cancelText:  'Atcelt',
    });

    if (!confirmed) return;

    if (activeRoutine.value?.id === routine.id) {
        localStorage.removeItem('activeRoutine');
        localStorage.setItem('routineChanged', 'true');
        activeRoutine.value = null;
    }

    closeModal();
    router.delete(route('routines.destroy', { routine: routine.id }), {
        onError: async () => await error('Kļūda dzēšot rutīnu.'),
    });
};

const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && selectedRoutine.value) closeModal();
};

onMounted(() => {
    if (isAuthenticated.value) {
        const saved = localStorage.getItem('activeRoutine');
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                // Pārbauda vai saglabātā rutīna pieder šim lietotājam
                if (parsed?.user_id === props.auth?.user?.id) {
                    activeRoutine.value = parsed;
                } else {
                    // Svešs lietotājs — notīram cache
                    localStorage.removeItem('activeRoutine');
                }
            } catch {
                localStorage.removeItem('activeRoutine');
            }
        }
    }
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => document.removeEventListener('keydown', handleEscape));

const setAsActiveRoutine = async (routine: any) => {
    if (!isAuthenticated.value) return;
    try {
        const res  = await axios.post(`/routines/${routine.id}/set-active`);
        const data = res.data?.routine ?? {
            id: routine.id, name: routine.name,
            description: routine.description || '',
            is_public:   routine.is_public || false,
            exercises: (routine.exercises || []).map((ex: any) => ({
                id: ex.id, name: ex.name, muscle_group: ex.muscle_group || '',
                sets: ex.pivot?.sets || 3, reps: ex.pivot?.reps || 10,
                rest_seconds: ex.pivot?.rest_seconds || 60,
                day_number:   ex.pivot?.day_number || 1,
                notes:        ex.pivot?.notes || '',
            })),
        };

        localStorage.setItem('activeRoutine', JSON.stringify(data));
        localStorage.setItem('routineChanged', 'true');
        activeRoutine.value = data;

        await success(`Rutīna "${routine.name}" iestatīta kā aktīvā!`, 'Veiksmīgi!');
        closeModal();
    } catch (e) {
        await error('Neizdevās iestatīt rutīnu. Mēģiniet vēlreiz.');
    }
};

const clearActiveRoutine = async () => {
    if (!isAuthenticated.value) return;
    const confirmed = await confirm({
        title: 'Noņemt aktīvo rutīnu?',
        message: 'Vai tiešām vēlies noņemt aktīvo rutīnu?',
        confirmText: 'Noņemt', cancelText: 'Atcelt',
    });
    if (!confirmed) return;
    localStorage.removeItem('activeRoutine');
    localStorage.setItem('routineChanged', 'true');
    activeRoutine.value = null;
};

const viewRoutineDetails = (routine: any) => {
    selectedRoutine.value = routine;
    document.body.style.overflow = 'hidden';
};

const closeModal = () => {
    selectedRoutine.value = null;
    document.body.style.overflow = 'auto';
};

const startRoutineWorkout = async (routine: any) => {
    if (!isAuthenticated.value) return;
    try {
        const today    = new Date().getDay();
        const todayNum = today === 0 ? 7 : today;

        const todayExercises = (routine.exercises ?? [])
            .filter((e: any) => (e.pivot?.day_number ?? e.day_number ?? 1) === todayNum)
            .map((e: any) => ({
                id: e.id, name: e.name, muscle_group: e.muscle_group || '',
                sets: e.pivot?.sets ?? e.sets ?? 3,
                reps: e.pivot?.reps ?? e.reps ?? 10,
                rest_seconds: e.pivot?.rest_seconds ?? e.rest_seconds ?? 60,
                notes:      e.pivot?.notes ?? e.notes ?? '',
                day_number: e.pivot?.day_number ?? e.day_number ?? 1,
            }));

        if (!todayExercises.length) {
            const dn = ['Svētdiena','Pirmdiena','Otrdiena','Trešdiena','Ceturtdiena','Piektdiena','Sestdiena'];
            await error(`Šodien (${dn[today]}) nav vingrinājumu rutīnā "${routine.name}".`, 'Nav vingrinājumu');
            return;
        }

        const res = await axios.post('/workout/free/start', {
            name:       `${routine.name} - ${new Date().toLocaleDateString('lv-LV')}`,
            routine_id: routine.id,
            exercises:  todayExercises,
        });

        if (res.data?.success) {
            router.visit(res.data.session_id ? `/workout/free?session=${res.data.session_id}` : '/workout/free');
        } else {
            throw new Error(res.data?.message ?? 'Nezināma kļūda');
        }
    } catch (e: any) {
        await error('Kļūda: ' + (e.response?.data?.message ?? e.message));
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Manas rutīnas" />

        <div class="page">
            <div class="container">

                <div class="page-header">
                    <h1>Manas rutīnas</h1>
                    <div class="header-right">
                        <!-- Admin zīmīte -->
                        <span v-if="isAdmin" class="admin-badge">👑 Admin</span>
                        <Link v-if="isAuthenticated" href="/routines/create" class="btn btn--primary">
                        + Izveidot rutīnu
                        </Link>
                        <span v-else class="auth-notice">Pierakstieties, lai izveidotu rutīnas</span>
                    </div>
                </div>

                <!-- Aktīvās rutīnas josla -->
                <div v-if="isAuthenticated && activeRoutine" class="active-banner">
                    <div class="active-banner__inner">
                        <div>
                            <p class="active-banner__label">Aktīvā rutīna</p>
                            <h3 class="active-banner__name">{{ activeRoutine.name }}</h3>
                            <div class="active-banner__tags">
                                <span class="tag tag--white">{{ getTotalExercisesCount(activeRoutine) }} vingrinājumi</span>
                                <span v-if="activeRoutine.is_public" class="tag tag--white">Publiska</span>
                            </div>
                        </div>
                        <button @click="clearActiveRoutine" class="btn btn--ghost">Noņemt</button>
                    </div>
                </div>

                <!-- Šķirtājs — savas rutīnas -->
                <div v-if="isAuthenticated && sortedRoutines.some(r => r.user_id === auth?.user?.id)" class="section-label">
                    Manas rutīnas
                </div>

                <div class="grid">
                    <template v-for="(routine, index) in sortedRoutines" :key="routine.id">

                        <!-- Šķirtājs pirms svešajām rutīnām -->
                        <div v-if="index === firstOtherIndex && firstOtherIndex > 0"
                             class="grid-divider">
                            <span>Publiskās rutīnas</span>
                        </div>

                        <div :class="['card', routine.id === activeRoutine?.id && 'card--active', routine.user_id !== auth?.user?.id && 'card--other']">
                            <div class="card__head">
                                <h2>{{ routine.name }}</h2>
                                <div class="card__badges">
                                    <span v-if="isAuthenticated && routine.id === activeRoutine?.id" class="badge badge--orange">Aktīvā</span>
                                    <!-- Rāda autoru ja skatās svešu rutīnu -->
                                    <span v-if="routine.user_id !== auth?.user?.id && routine.user" class="badge badge--grey">{{ routine.user.name }}</span>
                                </div>
                            </div>

                            <p class="card__desc">{{ routine.description || 'Nav apraksta' }}</p>

                            <div class="card__meta">
                                <span class="tag">{{ getTotalExercisesCount(routine) }} vingrinājumi</span>
                                <span v-if="routine.is_public" class="tag tag--green">Publiska</span>
                            </div>

                            <div class="card__actions">
                                <button @click="viewRoutineDetails(routine)" class="btn btn--outline">Skatīt</button>
                                <button v-if="canEdit(routine)" @click="editRoutine(routine)" class="btn btn--dark">Rediģēt</button>
                                <button v-if="isAuthenticated" @click="startRoutineWorkout(routine)" class="btn btn--primary">Sākt</button>
                                <button v-if="isAuthenticated"
                                        @click="setAsActiveRoutine(routine)"
                                        :class="['btn', routine.id === activeRoutine?.id ? 'btn--active' : 'btn--outline']">
                                    {{ routine.id === activeRoutine?.id ? '★ Aktīvā' : 'Iestatīt' }}
                                </button>
                                <!-- Dzēst: admins var visu, lietotājs — tikai savējo -->
                                <button v-if="canDelete(routine)" @click="deleteRoutine(routine)" class="btn btn--danger">
                                    {{ isAdmin && routine.user_id !== auth?.user?.id ? '👑 Dzēst' : 'Dzēst' }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <div v-if="sortedRoutines.length === 0" class="empty">
                        <div class="empty__icon">🏋️</div>
                        <p>Vēl nav nevienas rutīnas.</p>
                        <Link v-if="isAuthenticated" href="/routines/create" class="btn btn--primary">
                        Izveidot pirmo rutīnu
                        </Link>
                        <div v-else class="empty__auth">
                            <p>Pierakstieties, lai sāktu!</p>
                            <Link href="/login" class="btn btn--dark">Pierakstīties</Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detaļu modāls -->
            <div v-if="selectedRoutine" class="overlay" @click.self="closeModal">
                <div class="modal">
                    <div class="modal__head">
                        <h2>{{ selectedRoutine.name }}</h2>
                        <button @click="closeModal" class="modal__close">✕</button>
                    </div>

                    <div class="modal__body">
                        <div class="modal__section">
                            <h3>Apraksts</h3>
                            <p>{{ selectedRoutine.description || 'Nav apraksta' }}</p>
                        </div>

                        <div class="modal__section">
                            <h3>Informācija</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-item__label">Vingrinājumi</span>
                                    <span class="info-item__val">{{ getTotalExercisesCount(selectedRoutine) }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-item__label">Statuss</span>
                                    <span :class="['badge', selectedRoutine.is_public ? 'badge--green' : 'badge--grey']">
                                        {{ selectedRoutine.is_public ? 'Publiska' : 'Privāta' }}
                                    </span>
                                </div>
                                <div v-if="selectedRoutine.user" class="info-item">
                                    <span class="info-item__label">Autors</span>
                                    <span class="info-item__val" style="font-size:0.95rem">{{ selectedRoutine.user.name }}</span>
                                </div>
                                <div v-if="isAuthenticated && selectedRoutine.id === activeRoutine?.id" class="info-item">
                                    <span class="info-item__label">Aktīvā</span>
                                    <span class="badge badge--orange">Jā</span>
                                </div>
                            </div>
                        </div>

                        <div class="modal__section">
                            <h3>Nedēļas grafiks</h3>
                            <div class="schedule">
                                <div v-for="day in 7" :key="day" class="day">
                                    <div class="day__head">
                                        <h4>{{ getDayName(day) }}</h4>
                                        <span class="badge badge--orange">{{ getExercisesCountForDay(selectedRoutine, day) }}</span>
                                    </div>
                                    <div v-if="getExercisesForDay(selectedRoutine, day).length" class="ex-list">
                                        <div v-for="ex in getExercisesForDay(selectedRoutine, day)" :key="ex.id ?? ex.name" class="ex-card">
                                            <div class="ex-card__head">
                                                <h5>{{ ex.name }}</h5>
                                                <span class="tag tag--orange">{{ ex.muscle_group }}</span>
                                            </div>
                                            <div class="specs">
                                                <div class="spec"><span>Seti</span><strong>{{ ex.pivot?.sets ?? ex.sets ?? 3 }}</strong></div>
                                                <div class="spec"><span>Reps</span><strong>{{ ex.pivot?.reps ?? ex.reps ?? 10 }}</strong></div>
                                                <div v-if="ex.pivot?.rest_seconds ?? ex.rest_seconds" class="spec">
                                                    <span>Atpūta</span><strong>{{ ex.pivot?.rest_seconds ?? ex.rest_seconds }}s</strong>
                                                </div>
                                            </div>
                                            <div v-if="ex.pivot?.notes ?? ex.notes" class="ex-notes">{{ ex.pivot?.notes ?? ex.notes }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="rest-day">Atpūtas diena</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal__foot">
                        <button v-if="isAuthenticated" @click="startRoutineWorkout(selectedRoutine)" class="btn btn--primary">Sākt treniņu</button>
                        <button v-if="canEdit(selectedRoutine)" @click="editRoutine(selectedRoutine)" class="btn btn--dark">Rediģēt</button>
                        <button v-if="isAuthenticated"
                                @click="setAsActiveRoutine(selectedRoutine)"
                                :class="['btn', selectedRoutine.id === activeRoutine?.id ? 'btn--active' : 'btn--outline']">
                            {{ selectedRoutine.id === activeRoutine?.id ? '★ Aktīvā' : 'Iestatīt kā aktīvo' }}
                        </button>
                        <button v-if="canDelete(selectedRoutine)" @click="deleteRoutine(selectedRoutine)" class="btn btn--danger">Dzēst</button>
                        <button @click="closeModal" class="btn btn--outline">Aizvērt</button>
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
        color: #111827;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Galvene */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.25rem;
        border-bottom: 2px solid #e5e7eb;
    }

        .page-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

    .header-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Admin nozīmīte galvenē */
    .admin-badge {
        background: #111827;
        color: #fbbf24;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        border: 1px solid #374151;
    }

    .auth-notice {
        font-size: 0.875rem;
        color: #6b7280;
        background: #fff;
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }

    /* Aktīvās josla */
    .active-banner {
        background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
        border-radius: 0.875rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 14px rgba(230,92,0,0.25);
    }

    .active-banner__inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .active-banner__label {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.75);
        margin: 0 0 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .active-banner__name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #fff;
        margin: 0 0 0.6rem;
    }

    .active-banner__tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Sadaļas uzraksts */
    .section-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 0.875rem;
    }

    /* Režģis — šķirtājs aizņem visu platumu */
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.25rem;
    }

    .grid-divider {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0.5rem 0;
    }

        .grid-divider span {
            font-size: 0.75rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }

        .grid-divider::before,
        .grid-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

    /* Karte */
    .card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        padding: 1.5rem;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

    /* Svešas rutīnas — nedaudz blāvākas */
    .card--other {
        background: #fafafa;
    }

    /* Aktīvā — oranža josla augšā */
    .card--active {
        border-color: #ff8c42;
        position: relative;
    }

        .card--active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff8c42, #e65c00);
            border-radius: 1rem 1rem 0 0;
        }

    .card__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
        gap: 0.5rem;
    }

        .card__head h2 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            flex: 1;
        }

    .card__badges {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        align-items: flex-start;
    }

    .card__desc {
        color: #6b7280;
        font-size: 0.9rem;
        line-height: 1.5;
        margin: 0 0 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .card__meta {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid #f3f4f6;
        flex-wrap: wrap;
    }

    .card__actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Tagetes */
    .tag {
        background: #f3f4f6;
        color: #6b7280;
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid #e5e7eb;
    }

    .tag--green {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }

    .tag--orange {
        background: #fff7ed;
        color: #e65c00;
        border-color: #fed7aa;
    }

    .tag--white {
        background: rgba(255,255,255,0.25);
        color: #fff;
        border-color: rgba(255,255,255,0.3);
    }

    /* Nozīmītes */
    .badge {
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge--orange {
        background: #fff7ed;
        color: #e65c00;
        border: 1px solid #fed7aa;
    }

    .badge--green {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .badge--grey {
        background: #f9fafb;
        color: #6b7280;
        border: 1px solid #e5e7eb;
    }

    /* Tukšs stāvoklis */
    .empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 4rem 2rem;
        color: #9ca3af;
        background: #fff;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
    }

    .empty__icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }

    .empty p {
        font-size: 1rem;
        margin-bottom: 1.25rem;
        color: #6b7280;
    }

    .empty__auth {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    /* Pogas */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.55rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.85rem;
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

    .btn--dark {
        background: #111827;
        color: #fff;
    }

        .btn--dark:hover {
            background: #1f2937;
        }

    .btn--active {
        background: #fff7ed;
        color: #e65c00;
        border: 1px solid #fed7aa;
    }

        .btn--active:hover {
            background: #ffedd5;
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

    .btn--ghost {
        background: rgba(255,255,255,0.2);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.35);
    }

        .btn--ghost:hover {
            background: rgba(255,255,255,0.3);
        }

    .btn--danger {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

        .btn--danger:hover {
            background: #dc2626;
            color: #fff;
        }

    /* Detaļu modāls */
    .overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
        animation: fadeIn 0.2s ease;
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
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        width: 100%;
        max-width: 680px;
        max-height: 88vh;
        overflow-y: auto;
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        animation: slideUp 0.25s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 1;
    }

        .modal__head h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

    .modal__close {
        background: none;
        border: none;
        color: #9ca3af;
        font-size: 1.4rem;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        transition: color 0.2s, background 0.2s;
        line-height: 1;
    }

        .modal__close:hover {
            color: #111827;
            background: #f3f4f6;
        }

    .modal__body {
        padding: 1.5rem;
    }

    .modal__section {
        margin-bottom: 1.75rem;
    }

        .modal__section h3 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #e65c00;
            margin-bottom: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .modal__section > p {
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
        }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
    }

    .info-item {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.625rem;
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .info-item__label {
        font-size: 0.75rem;
        color: #9ca3af;
        font-weight: 500;
    }

    .info-item__val {
        font-weight: 700;
        color: #111827;
        font-size: 1.125rem;
    }

    .schedule {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .day {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1rem;
    }

    .day__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

        .day__head h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

    .ex-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .rest-day {
        text-align: center;
        padding: 0.75rem;
        color: #9ca3af;
        font-style: italic;
        font-size: 0.8rem;
        border: 1px dashed #e5e7eb;
        border-radius: 0.5rem;
        background: #fff;
    }

    .ex-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.625rem;
        padding: 0.875rem;
    }

    .ex-card__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.625rem;
        gap: 0.5rem;
    }

        .ex-card__head h5 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
            flex: 1;
        }

    .specs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.4rem;
        margin-bottom: 0.4rem;
    }

    .spec {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        padding: 0.35rem 0.5rem;
        display: flex;
        flex-direction: column;
    }

        .spec span {
            font-size: 0.65rem;
            color: #9ca3af;
        }

        .spec strong {
            font-size: 0.875rem;
            color: #ff8c42;
            font-weight: 700;
        }

    .ex-notes {
        font-size: 0.78rem;
        color: #6b7280;
        padding: 0.375rem 0.5rem;
        background: #f9fafb;
        border-radius: 0.375rem;
        border: 1px solid #e5e7eb;
        font-style: italic;
    }

    .modal__foot {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid #f3f4f6;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        position: sticky;
        bottom: 0;
        background: #fff;
    }

    .modal::-webkit-scrollbar {
        width: 5px;
    }

    .modal::-webkit-scrollbar-track {
        background: #f9fafb;
    }

    .modal::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
    }

    /* Mobilais */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .grid {
            grid-template-columns: 1fr;
        }

        .active-banner__inner {
            flex-direction: column;
            align-items: flex-start;
        }

        .card__actions {
            flex-direction: column;
        }

            .card__actions .btn {
                width: 100%;
            }

        .modal__foot {
            flex-direction: column;
        }

            .modal__foot .btn {
                width: 100%;
            }

        .specs {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 480px) {
        .page {
            padding: 1rem 0.5rem;
        }

        .modal {
            margin: 0.25rem;
            border-radius: 0.75rem;
        }

        .modal__head, .modal__body, .modal__foot {
            padding: 1rem;
        }

        .day__head {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.4rem;
        }
    }
</style>
