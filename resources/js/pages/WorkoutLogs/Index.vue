<template>
    <AppLayout>
        <div class="workout-logs-container">
            <div class="workout-logs">
                <!-- Header -->
                <div class="welcome-header">
                    <div class="header-left">
                        <h1>Treniņu vēsture 📋</h1>
                        <p>Skaties visus iepriekšējos treniņus un analizē savu progresu</p>
                    </div>
                    <div class="date-display">
                        <svg class="date-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="date-text">{{ currentDate }}</span>
                    </div>
                </div>

                <!-- Statistikas kartiņas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ stats.total_workouts }}</p>
                            <p class="stat-label">Kopējais treniņu skaits</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ formatMinutes(stats.total_duration) }}</p>
                            <p class="stat-label">Kopējais laiks</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ stats.total_calories?.toLocaleString() || 0 }}</p>
                            <p class="stat-label">Pazudušās kalorijas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="stat-content">
                            <p class="stat-number">{{ stats.this_month_workouts }}</p>
                            <p class="stat-label">Šī mēneša treniņi</p>
                        </div>
                    </div>
                </div>

                <!-- Filtrēšanas paneļis -->
                <div class="filter-card">
                    <div class="filter-content">
                        <div class="search-box">
                            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input v-model="filters.search"
                                   @input="debouncedSearch"
                                   type="text"
                                   placeholder="Meklēt pēc nosaukuma vai rutīnas..."
                                   class="search-input">
                        </div>

                        <div class="filter-controls">
                            <select v-model="filters.month" @change="filterByMonth" class="month-select">
                                <option value="">Visi mēneši</option>
                                <option v-for="month in months" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>

                            <button @click="resetFilters" class="clear-btn">
                                Notīrīt filtrus
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Treniņu saraksts -->
                <div v-if="workoutLogs.data.length > 0" class="workout-table-section">
                    <div class="table-wrapper">
                        <table class="workout-table">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="table-header">
                                            <svg class="table-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Datums
                                        </div>
                                    </th>
                                    <th>
                                        <div class="table-header">
                                            <svg class="table-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Treniņš
                                        </div>
                                    </th>
                                    <th>
                                        <div class="table-header">
                                            <svg class="table-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Ilgums
                                        </div>
                                    </th>
                                    <th>
                                        <div class="table-header">
                                            <svg class="table-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            Statistika
                                        </div>
                                    </th>
                                    <th>
                                        <div class="table-header">
                                            <svg class="table-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Darbības
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in workoutLogs.data" :key="log.id" class="table-row">
                                    <td class="date-cell">
                                        <div class="date-primary">{{ formatDate(log.completed_at) }}</div>
                                        <div class="date-secondary">{{ formatTime(log.completed_at) }}</div>
                                    </td>
                                    <td>
                                        <div class="workout-info">
                                            <div class="workout-title">{{ log.name }}</div>
                                            <div v-if="log.routine" class="routine-indicator">
                                                <span class="routine-badge">{{ log.routine.name }}</span>
                                            </div>
                                            <div v-if="log.notes" class="workout-notes">
                                                {{ truncateText(log.notes, 40) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="duration-info">
                                            <div class="duration-value">{{ log.duration_minutes }} min</div>
                                            <div v-if="log.calories_burned" class="calories-info">
                                                🔥 {{ log.calories_burned }} cal
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="stats-info">
                                            <div class="stats-item">
                                                <span class="stats-icon">🏋️</span>
                                                <span>{{ log.total_sets || 0 }} seti</span>
                                            </div>
                                            <div class="stats-item">
                                                <span class="stats-icon">🔄</span>
                                                <span>{{ log.total_reps || 0 }} atkārtojumi</span>
                                            </div>
                                            <div class="stats-item">
                                                <span class="stats-icon">⚖️</span>
                                                <span>{{ log.total_weight || 0 }} kg</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <Link :href="route('workout-logs.show', log.id)" class="btn-action view-btn">
                                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Skatīt
                                            </Link>
                                            <button @click="confirmDelete(log)" class="btn-action delete-btn">
                                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Dzēst
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Lapošana -->
                    <div class="pagination-section">
                        <div class="pagination-info">
                            Rāda {{ workoutLogs.from }} līdz {{ workoutLogs.to }} no {{ workoutLogs.total }} treniņiem
                        </div>
                        <div class="pagination-links">
                            <template v-for="(link, index) in workoutLogs.links" :key="index">
                                <Link v-if="link.url"
                                      :href="link.url"
                                      :class="['page-link', link.active ? 'active' : '']"
                                      v-html="link.label" />
                                <span v-else class="page-disabled" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Tukšs stāvoklis -->
                <div v-else class="empty-state">
                    <div class="empty-content">
                        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3>Nav treniņu vēstures</h3>
                        <p>
                            {{ filters.search || filters.month ? 'Neviens treniņš neatbilst jūsu meklēšanas kritērijiem.' : 'Jums vēl nav neviena pabeigta treniņa.' }}
                        </p>
                        <Link :href="route('dashboard')" class="btn-primary">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Sākt treniņu
                        </Link>
                    </div>
                </div>

                <!-- Dzēšanas dialogs -->
                <div v-if="showDeleteModal" class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <svg class="modal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <h3>Dzēst treniņu?</h3>
                        </div>
                        <div class="modal-body">
                            <p>Vai tiešām vēlaties dzēst treniņu <strong>"{{ workoutToDelete?.name }}"</strong>?</p>
                            <p class="modal-warning">⚠️ Šī darbība nevar tikt atcelta.</p>
                        </div>
                        <div class="modal-footer">
                            <button @click="showDeleteModal = false" class="btn-secondary">Atcelt</button>
                            <button @click="deleteWorkout" class="btn-danger">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Dzēst treniņu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
    import { ref } from 'vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { debounce } from 'lodash';

    const page = usePage();

    // Props
    const props = defineProps({
        workoutLogs: Object,
        filters: Object,
        stats: Object,
        months: Array
    });

    // Reactive state
    const showDeleteModal = ref(false);
    const workoutToDelete = ref<any>(null);
    const filters = ref({
        search: props.filters?.search || '',
        month: props.filters?.month || ''
    });

    // Pašreizējais datums
    const currentDate = ref(new Date().toLocaleDateString('lv-LV', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }));

    // Formatēšanas funkcijas
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('lv-LV', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const formatTime = (dateString: string) => {
        return new Date(dateString).toLocaleTimeString('lv-LV', {
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatMinutes = (minutes: number) => {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
    };

    const truncateText = (text: string, length: number) => {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    };

    // Filtrēšana
    const debouncedSearch = debounce(() => {
        updateFilters();
    }, 500);

    const filterByMonth = () => {
        updateFilters();
    };

    const updateFilters = () => {
        router.get(route('workout-logs.index'), {
            search: filters.value.search,
            month: filters.value.month
        }, {
            preserveState: true,
            replace: true
        });
    };

    const resetFilters = () => {
        filters.value.search = '';
        filters.value.month = '';
        updateFilters();
    };

    // Dzēšana
    const confirmDelete = (workout: any) => {
        workoutToDelete.value = workout;
        showDeleteModal.value = true;
    };

    const deleteWorkout = async () => {
        if (!workoutToDelete.value) return;

        try {
            await router.delete(route('workout-logs.destroy', workoutToDelete.value.id));
            showDeleteModal.value = false;
            workoutToDelete.value = null;
        } catch (error) {
            console.error('Delete error:', error);
            alert('Kļūda dzēšot treniņu');
        }
    };
</script>

<style scoped>
    /* Vispārējā konteinera stili */
    .workout-logs-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .workout-logs {
        padding: 2rem;
        max-width: 1440px;
        margin: 0 auto;
    }

    /* Header */
    .welcome-header {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 3rem;
        padding: 2.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    @media (min-width: 768px) {
        .welcome-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .header-left h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
        color: #1e293b;
        letter-spacing: -0.025em;
    }

    .header-left p {
        font-size: 1.125rem;
        color: #64748b;
        line-height: 1.6;
        max-width: 600px;
    }

    .date-display {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        padding: 0.875rem 1.5rem;
        border-radius: 0.875rem;
        font-size: 1rem;
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.2);
    }

    .date-icon {
        width: 1.5rem;
        height: 1.5rem;
    }

    /* Stats Grid - Moderno kartiņu stils */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: 1.25rem;
        padding: 2rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            border-color: #e2e8f0;
        }

    .stat-icon {
        width: 4rem;
        height: 4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border-radius: 1rem;
        margin-right: 1.5rem;
        flex-shrink: 0;
        box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
    }

        .stat-icon svg {
            width: 2rem;
            height: 2rem;
        }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .stat-label {
        font-size: 1rem;
        color: #64748b;
        font-weight: 500;
    }

    /* Filter card - Modernais dizains */
    .filter-card {
        background: white;
        border-radius: 1.25rem;
        padding: 2rem;
        margin-bottom: 3rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }

        .filter-card:hover {
            border-color: #e2e8f0;
        }

    .filter-content {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .filter-content {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .search-box {
        position: relative;
        flex: 1;
    }

    .search-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.5rem;
        height: 1.5rem;
        color: #8b5cf6;
        z-index: 1;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1.25rem 1rem 3.5rem;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        font-size: 1rem;
        color: #1e293b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
    }

        .search-input:focus {
            outline: none;
            border-color: #8b5cf6;
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

        .search-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

    .filter-controls {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .month-select {
        padding: 1rem 1.25rem;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        font-size: 1rem;
        color: #1e293b;
        min-width: 180px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%238b5cf6' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 1rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 3rem;
    }

        .month-select:focus {
            outline: none;
            border-color: #8b5cf6;
            background: white;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

    .clear-btn {
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: #475569;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: -0.025em;
    }

        .clear-btn:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

    /* Workout table section - Modernais dizains */
    .workout-table-section {
        background: white;
        border-radius: 1.25rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        overflow: hidden;
        transition: all 0.3s ease;
    }

        .workout-table-section:hover {
            border-color: #e2e8f0;
        }

    .table-wrapper {
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

    .workout-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

        .workout-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .workout-table th {
            padding: 1.5rem;
            text-align: left;
            font-weight: 700;
            color: #475569;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

    .table-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .table-icon {
        width: 1.25rem;
        height: 1.25rem;
        color: #8b5cf6;
    }

    .table-row {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.2s ease;
    }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
        }

    .workout-table td {
        padding: 1.5rem;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Table cells styling - Modernizēts */
    .date-cell .date-primary {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        margin-bottom: 0.375rem;
    }

    .date-cell .date-secondary {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
    }

    .workout-info .workout-title {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.125rem;
        margin-bottom: 0.5rem;
        letter-spacing: -0.025em;
    }

    .routine-indicator .routine-badge {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, #e0e7ff 0%, #dbeafe 100%);
        color: #4f46e5;
        padding: 0.375rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        border: 1px solid #c7d2fe;
    }

    .workout-notes {
        font-size: 0.875rem;
        color: #64748b;
        line-height: 1.5;
        max-width: 300px;
        white-space: normal;
    }

    .duration-info .duration-value {
        font-weight: 700;
        color: #1e293b;
        font-size: 1.125rem;
        margin-bottom: 0.375rem;
    }

    .calories-info {
        font-size: 0.875rem;
        color: #ef4444;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .stats-info {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .stats-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.875rem;
        color: #475569;
        font-weight: 500;
    }

    .stats-icon {
        font-size: 1rem;
        width: 1.5rem;
        text-align: center;
    }

    /* Action buttons - Modernais dizains */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        min-width: 200px;
    }

    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        border: 2px solid transparent;
        letter-spacing: -0.025em;
    }

    .view-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        border-color: #3b82f6;
    }

        .view-btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }

    .delete-btn {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: #dc2626;
        border-color: #e2e8f0;
    }

        .delete-btn:hover {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #b91c1c;
            border-color: #fca5a5;
            transform: translateY(-2px);
        }

    .btn-icon {
        width: 1.25rem;
        height: 1.25rem;
    }

    /* Pagination - Modernais dizains */
    .pagination-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        padding: 2rem;
        border-top: 1px solid #f1f5f9;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        gap: 1.5rem;
    }

    @media (min-width: 640px) {
        .pagination-section {
            flex-direction: row;
        }
    }

    .pagination-info {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
    }

    .pagination-links {
        display: flex;
        gap: 0.375rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-link {
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        border: 2px solid #e2e8f0;
        color: #475569;
        background: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 2.5rem;
        text-align: center;
    }

        .page-link:hover {
            background: #f8fafc;
            border-color: #8b5cf6;
            color: #8b5cf6;
            transform: translateY(-1px);
        }

        .page-link.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border-color: #8b5cf6;
        }

    .page-disabled {
        padding: 0.75rem 1rem;
        color: #cbd5e1;
        font-size: 0.875rem;
        font-weight: 600;
    }

    /* Empty state - Modernais dizains */
    .empty-state {
        background: white;
        border-radius: 1.25rem;
        padding: 4rem 2rem;
        text-align: center;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }

        .empty-state:hover {
            border-color: #e2e8f0;
        }

    .empty-content {
        max-width: 32rem;
        margin: 0 auto;
    }

    .empty-icon {
        width: 5rem;
        height: 5rem;
        color: #cbd5e1;
        margin: 0 auto 2rem;
        stroke-width: 1.5;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
        letter-spacing: -0.025em;
    }

    .empty-state p {
        color: #64748b;
        margin-bottom: 2rem;
        line-height: 1.6;
        font-size: 1.125rem;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 2.5rem;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        border: none;
        border-radius: 1rem;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: -0.025em;
        box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
    }

        .btn-primary:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.4);
        }

    /* Modal - Modernais dizains */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        z-index: 50;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: white;
        border-radius: 1.5rem;
        max-width: 32rem;
        width: 100%;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        animation: modalSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .modal-icon {
        width: 2.5rem;
        height: 2.5rem;
        flex-shrink: 0;
    }

    .modal-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.025em;
    }

    .modal-body {
        padding: 2rem;
    }

        .modal-body p {
            color: #475569;
            margin-bottom: 1rem;
            line-height: 1.6;
            font-size: 1.125rem;
        }

    .modal-warning {
        color: #dc2626;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .modal-footer {
        padding: 1.5rem 2rem 2rem;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    .btn-secondary {
        padding: 0.875rem 2rem;
        background: white;
        color: #475569;
        border: 2px solid #e2e8f0;
        border-radius: 0.875rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: -0.025em;
    }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

    .btn-danger {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 2rem;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
        border-radius: 0.875rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        letter-spacing: -0.025em;
        box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
    }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(239, 68, 68, 0.4);
        }
</style>
