<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash';

const props = defineProps({
    workoutLogs: Object,
    filters:     Object,
    stats:       Object,
    months:      Array
});

const showDeleteModal = ref(false);
const workoutToDelete = ref<any>(null);

const filters = ref({
    search: props.filters?.search || '',
    month:  props.filters?.month  || ''
});

const currentDate = new Date().toLocaleDateString('lv-LV', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
});

const formatDate = (d: string) => new Date(d).toLocaleDateString('lv-LV', {
    year: 'numeric', month: 'short', day: 'numeric'
});

const formatTime = (d: string) => new Date(d).toLocaleTimeString('lv-LV', {
    hour: '2-digit', minute: '2-digit'
});

const formatMinutes = (min: number) => {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const truncate = (text: string, len: number) =>
    text && text.length > len ? text.substring(0, len) + '...' : text;

const updateFilters = () => router.get(route('workout-logs.index'), {
    search: filters.value.search,
    month:  filters.value.month
}, { preserveState: true, replace: true });

const debouncedSearch = debounce(updateFilters, 500);

const resetFilters = () => {
    filters.value.search = '';
    filters.value.month  = '';
    updateFilters();
};

const confirmDelete = (log: any) => {
    workoutToDelete.value = log;
    showDeleteModal.value = true;
};

const deleteWorkout = () => {
    if (!workoutToDelete.value) return;
    router.delete(route('workout-logs.destroy', workoutToDelete.value.id));
    showDeleteModal.value = false;
    workoutToDelete.value = null;
};
</script>

<template>
    <AppLayout>
        <div class="page">

            <!-- Galvene -->
            <div class="topbar">
                <div>
                    <h1 class="topbar-title">Treniņu vēsture</h1>
                    <p class="topbar-sub">Skaties visus iepriekšējos treniņus</p>
                </div>
                <div class="topbar-date">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ currentDate }}
                </div>
            </div>

            <!-- Statistikas kartiņas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-num">{{ stats.total_workouts }}</div>
                        <div class="stat-lbl">Kopējais treniņu skaits</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-num">{{ formatMinutes(stats.total_duration) }}</div>
                        <div class="stat-lbl">Kopējais laiks</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-num">{{ stats.this_month_workouts }}</div>
                        <div class="stat-lbl">Šī mēneša treniņi</div>
                    </div>
                </div>
            </div>

            <!-- Filtri -->
            <div class="filter-card">
                <div class="filter-row">
                    <div class="search-wrap">
                        <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="filters.search" @input="debouncedSearch"
                               type="text" placeholder="Meklēt treniņus..." class="search-input" />
                    </div>
                    <select v-model="filters.month" @change="updateFilters" class="month-select">
                        <option value="">Visi mēneši</option>
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                    <button @click="resetFilters" class="btn-clear">Notīrīt</button>
                </div>
            </div>

            <!-- Tabula -->
            <div v-if="workoutLogs.data.length > 0" class="table-card">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Datums</th>
                                <th>Treniņš</th>
                                <th>Ilgums</th>
                                <th>Statistika</th>
                                <th>Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in workoutLogs.data" :key="log.id">
                                <td>
                                    <div class="date-main">{{ formatDate(log.completed_at) }}</div>
                                    <div class="date-time">{{ formatTime(log.completed_at) }}</div>
                                </td>
                                <td>
                                    <div class="log-name">{{ log.name }}</div>
                                    <span v-if="log.routine" class="routine-badge">{{ log.routine.name }}</span>
                                    <div v-if="log.notes" class="log-notes">{{ truncate(log.notes, 40) }}</div>
                                </td>
                                <td>
                                    <div class="duration">{{ log.duration_minutes }} min</div>
                                </td>
                                <td>
                                    <div class="stat-row">🏋️ {{ log.total_sets || 0 }} seti</div>
                                    <div class="stat-row">🔄 {{ log.total_reps || 0 }} atkārtojumi</div>
                                    <div class="stat-row">⚖️ {{ log.total_weight || 0 }} kg</div>
                                </td>
                                <td>
                                    <div class="action-col">
                                        <Link :href="route('workout-logs.show', log.id)" class="btn-view">
                                        Skatīt
                                        </Link>
                                        <button @click="confirmDelete(log)" class="btn-del">Dzēst</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Lapošana -->
                <div class="pagination">
                    <div class="pagination-info">
                        Rāda {{ workoutLogs.from }}–{{ workoutLogs.to }} no {{ workoutLogs.total }}
                    </div>
                    <div class="pagination-links">
                        <template v-for="(link, i) in workoutLogs.links" :key="i">
                            <Link v-if="link.url" :href="link.url"
                                  :class="['page-btn', link.active ? 'page-active' : '']"
                                  v-html="link.label" />
                            <span v-else class="page-disabled" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Tukšs stāvoklis -->
            <div v-else class="empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="56" height="56" style="color:#d1d5db;margin-bottom:1rem">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3>Nav treniņu vēstures</h3>
                <p>{{ filters.search || filters.month ? 'Neviens treniņš neatbilst filtriem.' : 'Vēl nav neviena pabeigta treniņa.' }}</p>
                <Link :href="route('dashboard')" class="btn-start">Sākt treniņu</Link>
            </div>

        </div>

        <!-- Dzēšanas modāls -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal">
                    <div class="modal-header">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <h3>Dzēst treniņu?</h3>
                    </div>
                    <div class="modal-body">
                        <p>Vai tiešām vēlaties dzēst <strong>{{ workoutToDelete?.name }}</strong>?</p>
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
        background: #f3f4f6;
        min-height: 100vh;
    }

    /* Galvene */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1.5rem 1.5rem;
        margin: 0 -1rem;
        background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
        box-shadow: 0 2px 12px rgba(230, 92, 0, 0.3);
        margin-bottom: 1.5rem;
    }

    .topbar-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.15rem;
    }

    .topbar-sub {
        font-size: 0.875rem;
        color: rgba(255,255,255,0.8);
    }

    .topbar-date {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(0,0,0,0.18);
        color: white;
        padding: 0.45rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* Statistika */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.25rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.625rem;
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-num {
        font-size: 1.625rem;
        font-weight: 700;
        color: #111827;
        line-height: 1;
        margin-bottom: 0.2rem;
    }

    .stat-lbl {
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 500;
    }

    /* Filtri */
    .filter-card {
        background: white;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        margin-bottom: 1.25rem;
    }

    .filter-row {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .search-wrap {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .search-icon {
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    .search-input {
        width: 100%;
        padding: 0.6rem 0.875rem 0.6rem 2.625rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: #111827;
        background: #f9fafb;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

        .search-input:focus {
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.1);
            background: white;
        }

    .month-select {
        padding: 0.6rem 2rem 0.6rem 0.875rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: #111827;
        background: #f9fafb;
        outline: none;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23ff8c42' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.25em;
        transition: border-color 0.15s;
    }

        .month-select:focus {
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255,140,66,0.1);
        }

    .btn-clear {
        padding: 0.6rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: white;
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s;
    }

        .btn-clear:hover {
            background: #f3f4f6;
            color: #374151;
        }

    /* Tabula */
    .table-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .table-wrap {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

        .table thead tr {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            padding: 0.875rem 1rem;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.1s;
        }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .table tbody tr:hover {
                background: #fff7ed;
            }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

    .date-main {
        font-weight: 600;
        color: #111827;
        font-size: 0.875rem;
    }

    .date-time {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 0.15rem;
    }

    .log-name {
        font-weight: 600;
        color: #111827;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .log-notes {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 0.2rem;
    }

    .routine-badge {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 600;
        color: #e65c00;
        background: #fff7ed;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        border: 1px solid #fed7aa;
    }

    .duration {
        font-weight: 600;
        color: #111827;
        font-size: 0.875rem;
    }

    .stat-row {
        font-size: 0.8rem;
        color: #4b5563;
        margin-bottom: 0.2rem;
    }

        .stat-row:last-child {
            margin-bottom: 0;
        }

    .action-col {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        min-width: 90px;
    }

    .btn-view {
        display: block;
        text-align: center;
        padding: 0.4rem 0.75rem;
        background: #ff8c42;
        color: white;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.15s;
    }

        .btn-view:hover {
            background: #e65c00;
        }

    .btn-del {
        display: block;
        width: 100%;
        padding: 0.4rem 0.75rem;
        background: white;
        color: #ef4444;
        border: 1px solid #fca5a5;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }

        .btn-del:hover {
            background: #fef2f2;
            border-color: #ef4444;
        }

    /* Lapošana */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-top: 1px solid #f3f4f6;
        background: #f9fafb;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .pagination-info {
        font-size: 0.8rem;
        color: #6b7280;
    }

    .pagination-links {
        display: flex;
        gap: 0.25rem;
        flex-wrap: wrap;
    }

    .page-btn {
        padding: 0.35rem 0.65rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        color: #374151;
        text-decoration: none;
        background: white;
        transition: all 0.15s;
    }

        .page-btn:hover {
            border-color: #ff8c42;
            color: #ff8c42;
        }

    .page-active {
        background: #ff8c42;
        border-color: #ff8c42;
        color: white;
    }

    .page-disabled {
        padding: 0.35rem 0.65rem;
        color: #d1d5db;
        font-size: 0.8rem;
    }

    /* Tukšs stāvoklis */
    .empty {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        padding: 4rem 2rem;
        text-align: center;
    }

        .empty h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .empty p {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

    .btn-start {
        display: inline-block;
        padding: 0.65rem 1.5rem;
        background: linear-gradient(135deg, #ff8c42, #e65c00);
        color: white;
        border-radius: 0.5rem;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.9rem;
        transition: opacity 0.15s;
    }

        .btn-start:hover {
            opacity: 0.9;
        }

    /* Modāls */
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
        max-width: 26rem;
        width: 100%;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

        .modal-header h3 {
            font-size: 1.125rem;
            font-weight: 700;
            margin: 0;
        }

    .modal-body {
        padding: 1.25rem 1.5rem;
    }

        .modal-body p {
            color: #4b5563;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

    .modal-warn {
        color: #dc2626;
        font-weight: 500;
        font-size: 0.8rem !important;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 1rem 1.5rem 1.25rem;
    }

    .btn-cancel {
        padding: 0.5rem 1.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: white;
        color: #6b7280;
        font-weight: 500;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.15s;
    }

        .btn-cancel:hover {
            background: #f3f4f6;
        }

    .btn-confirm {
        padding: 0.5rem 1.25rem;
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

    /* Mobilais */
    @media (max-width: 480px) {
        .topbar {
            padding: 1rem;
        }

        .topbar-date {
            display: none;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .filter-row {
            flex-direction: column;
        }

        .search-wrap {
            min-width: 0;
            width: 100%;
        }

        .month-select {
            width: 100%;
        }

        .btn-clear {
            width: 100%;
            text-align: center;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            display: none;
        }

        .pagination {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
