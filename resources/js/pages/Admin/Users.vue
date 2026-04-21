<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, User, Mail, Shield, ShieldAlert, Trash2, CheckCircle, Search, Calendar } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at: string;
    routines_count: number;
    goals_count: number;
    workout_logs_count: number;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    stats: {
        total_users: number;
        total_admins: number;
        active_users_7d: number;
    };
}

const props = defineProps<Props>();

const searchQuery      = ref('');
const processingUserId = ref<number | null>(null);

// Sūta meklēšanas pieprasījumu
const searchUsers = () => {
    router.get(route('admin.users'), { search: searchQuery.value }, {
        preserveState:  true,
        preserveScroll: true,
    });
};

// Aizkavēta meklēšana — negaida katru burtu
let searchTimeout: NodeJS.Timeout;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchUsers, 300);
};

// Piešķir vai atņem admin tiesības
const toggleAdmin = (user: User) => {
    const action = user.is_admin ? 'noņemt administratora tiesības' : 'piešķirt administratora tiesības';
    if (!confirm(`Vai tiešām vēlaties ${action} lietotājam ${user.name}?`)) return;
    processingUserId.value = user.id;
    router.post(route('admin.users.toggle-admin', user.id), {}, {
        preserveScroll: true,
        onFinish: () => { processingUserId.value = null; },
    });
};

// Dzēš lietotāju kopā ar visiem datiem
const deleteUser = (user: User) => {
    if (!confirm(`Vai tiešām vēlaties dzēst lietotāju ${user.name}? Tas neatgriezeniski dzēsīs visus viņa datus.`)) return;
    processingUserId.value = user.id;
    router.delete(route('admin.users.delete', user.id), {
        preserveScroll: true,
        onFinish: () => { processingUserId.value = null; },
    });
};

// Datums latviski bez ārējām bibliotēkām
const formatDate = (date: string | null) => {
    if (!date) return 'Nav apstiprināts';
    return new Date(date).toLocaleDateString('lv-LV', { year: 'numeric', month: 'long', day: 'numeric' });
};

// CSS klase lomas nozīmītei
const getRoleBadgeClass = (isAdmin: boolean) =>
    isAdmin ? 'bg-purple-100 text-purple-800 border-purple-200' : 'bg-gray-100 text-gray-700 border-gray-200';

// CSS klase e-pasta verifikācijas nozīmītei
const getVerificationBadgeClass = (isVerified: boolean) =>
    isVerified ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200';
</script>

<template>
    <AppLayout>
        <Head title="Administrators - Lietotāju pārvaldība" />

        <div class="admin-page-container">
            <div class="container-wrapper">

                <!-- Galvene -->
                <div class="header-section">
                    <div class="header-content">
                        <div>
                            <h1 class="main-title">Lietotāju pārvaldība</h1>
                            <p class="description-text">Pārvaldiet visus reģistrētos lietotājus un viņu lomas</p>
                        </div>
                        <div class="stats-badge">
                            <Users class="stats-icon" />
                            <span>{{ stats.total_users }} Kopā lietotāju</span>
                        </div>
                    </div>
                </div>

                <!-- Statistikas kartiņas -->
                <div class="stats-grid">
                    <Card class="stat-card">
                        <CardContent class="stat-card-content">
                            <div class="stat-icon-wrapper users-icon"><Users class="stat-icon" /></div>
                            <div class="stat-info">
                                <p class="stat-value">{{ stats.total_users }}</p>
                                <p class="stat-label">Kopā lietotāju</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="stat-card">
                        <CardContent class="stat-card-content">
                            <div class="stat-icon-wrapper admins-icon"><Shield class="stat-icon" /></div>
                            <div class="stat-info">
                                <p class="stat-value">{{ stats.total_admins }}</p>
                                <p class="stat-label">Administratori</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="stat-card">
                        <CardContent class="stat-card-content">
                            <div class="stat-icon-wrapper active-icon"><Users class="stat-icon" /></div>
                            <div class="stat-info">
                                <p class="stat-value">{{ stats.active_users_7d }}</p>
                                <p class="stat-label">Jauni lietotāji (7 dienās)</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Galvenā tabula -->
                <Card class="users-card">
                    <CardHeader class="card-header">
                        <div class="header-icon-wrapper">
                            <div class="icon-circle"><Users class="icon" /></div>
                            <div>
                                <CardTitle class="card-title">Visi lietotāji</CardTitle>
                                <CardDescription class="card-description">Skatīt un pārvaldīt visus reģistrētos lietotājus</CardDescription>
                            </div>
                        </div>

                        <!-- Meklēšanas lauks -->
                        <div class="search-wrapper">
                            <div class="search-input-container">
                                <Search class="search-icon" />
                                <Input v-model="searchQuery" @input="onSearchInput"
                                       placeholder="Meklēt pēc vārda vai e-pasta..."
                                       class="search-input" />
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="card-content">
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th class="table-header">Lietotājs</th>
                                        <th class="table-header">E-pasts</th>
                                        <th class="table-header">Loma</th>
                                        <th class="table-header">Verifikācija</th>
                                        <th class="table-header">Rutīnas</th>
                                        <th class="table-header">Mērķi</th>
                                        <th class="table-header">Treniņi</th>
                                        <th class="table-header">Pievienojās</th>
                                        <th class="table-header actions-header">Darbības</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users.data" :key="user.id" class="table-row">
                                        <td class="user-cell">
                                            <div class="user-avatar">{{ user.name.charAt(0).toUpperCase() }}</div>
                                            <div class="user-info">
                                                <Link :href="route('admin.users.show', user.id)" class="user-name">
                                                {{ user.name }}
                                                </Link>
                                            </div>
                                        </td>
                                        <td class="email-cell">
                                            <div class="email-wrapper">
                                                <Mail class="email-icon" />
                                                {{ user.email }}
                                            </div>
                                        </td>
                                        <td class="role-cell">
                                            <span :class="['role-badge', getRoleBadgeClass(user.is_admin)]">
                                                <Shield v-if="user.is_admin" class="badge-icon" />
                                                <User v-else class="badge-icon" />
                                                {{ user.is_admin ? 'Administrators' : 'Lietotājs' }}
                                            </span>
                                        </td>
                                        <td class="verification-cell">
                                            <span :class="['verification-badge', getVerificationBadgeClass(!!user.email_verified_at)]">
                                                <CheckCircle v-if="user.email_verified_at" class="badge-icon" />
                                                <ShieldAlert v-else class="badge-icon" />
                                                {{ user.email_verified_at ? 'Apstiprināts' : 'Gaida apstiprinājumu' }}
                                            </span>
                                        </td>
                                        <td class="stats-cell"><span class="stats-number">{{ user.routines_count }}</span></td>
                                        <td class="stats-cell"><span class="stats-number">{{ user.goals_count }}</span></td>
                                        <td class="stats-cell"><span class="stats-number">{{ user.workout_logs_count }}</span></td>
                                        <td class="date-cell">
                                            <Calendar class="date-icon" />
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="actions-cell">
                                            <div class="action-buttons">
                                                <Button @click="toggleAdmin(user)"
                                                        :disabled="processingUserId === user.id"
                                                        variant="outline" size="sm"
                                                        :class="['action-btn', user.is_admin ? 'demote-btn' : 'promote-btn']">
                                                    <Shield v-if="!user.is_admin" class="action-icon" />
                                                    <ShieldAlert v-else class="action-icon" />
                                                    {{ user.is_admin ? 'Atņemt tiesības' : 'Piešķirt tiesības' }}
                                                </Button>
                                                <Button @click="deleteUser(user)"
                                                        :disabled="processingUserId === user.id"
                                                        variant="outline" size="sm"
                                                        class="action-btn delete-btn">
                                                    <Trash2 class="action-icon" />
                                                    Dzēst
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Lapošana -->
                        <div v-if="users.last_page > 1" class="pagination-wrapper">
                            <div class="pagination-info">
                                Rāda {{ users.data.length }} no {{ users.total }} lietotājiem
                            </div>
                            <div class="pagination-buttons">
                                <Link v-for="link in users.links" :key="link.label"
                                      :href="link.url || '#'"
                                      :class="['pagination-link', { active: link.active, disabled: !link.url }]"
                                      v-html="link.label" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    .admin-page-container {
        min-height: 100vh;
        background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
    }

    .container-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .header-section {
        margin-bottom: 2rem;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .main-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
        letter-spacing: -0.025em;
    }

    .description-text {
        font-size: 1.125rem;
        color: #64748b;
        max-width: 600px;
        line-height: 1.6;
    }

    .stats-badge {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        background: white;
        border-radius: 9999px;
        border: 2px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        font-weight: 600;
        color: #1e293b;
    }

    .stats-icon {
        height: 1.25rem;
        width: 1.25rem;
        color: #f97316;
    }

    /* Statistikas kartiņas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        background: white;
        transition: all 0.2s;
    }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

    .stat-card-content {
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon-wrapper {
        padding: 0.75rem;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Katras kartiņas krāsa */
    .users-icon {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
    }

    .admins-icon {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .active-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .stat-icon {
        height: 1.5rem;
        width: 1.5rem;
        color: white;
    }

    .stat-info {
        flex: 1;
    }

    .stat-value {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    /* Galvenā tabulas kartīte */
    .users-card {
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        background: white;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-icon-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .icon-circle {
        padding: 0.75rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon {
        height: 1.25rem;
        width: 1.25rem;
        color: #f97316;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }

    .card-description {
        color: #64748b;
        font-size: 0.95rem;
        margin-top: 0.25rem;
    }

    .search-wrapper {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .search-input-container {
        position: relative;
    }

    .search-icon {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        height: 1rem;
        width: 1rem;
        color: #94a3b8;
    }

    .search-input {
        padding-left: 2.25rem;
        width: 250px;
    }

    /* Tabula */
    .users-table-container {
        overflow-x: auto;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-header {
        text-align: left;
        padding: 1rem;
        background: #f8fafc;
        font-weight: 600;
        color: #475569;
        font-size: 0.875rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .actions-header {
        text-align: center;
    }

    .table-row {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }

        .table-row:hover {
            background: #fff7ed;
        }

        .table-row td {
            padding: 1rem;
        }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Avatara burts — oranžs fons */
    .user-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316, #ea580c);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .user-name {
        font-weight: 600;
        color: #1e293b;
        text-decoration: none;
        transition: color 0.2s;
    }

        .user-name:hover {
            color: #f97316;
            text-decoration: underline;
        }

    .email-cell {
        color: #475569;
    }

    .email-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .email-icon {
        height: 1rem;
        width: 1rem;
        color: #94a3b8;
    }

    .role-badge, .verification-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid;
        width: fit-content;
    }

    .badge-icon {
        height: 0.875rem;
        width: 0.875rem;
    }

    .stats-cell {
        text-align: center;
    }

    .stats-number {
        font-weight: 600;
        color: #1e293b;
        background: #f1f5f9;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .date-cell {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    .date-icon {
        height: 0.875rem;
        width: 0.875rem;
        color: #94a3b8;
    }

    .actions-cell {
        text-align: center;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .action-btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    /* Admin piešķiršana/atņemšana */
    .promote-btn {
        border-color: #8b5cf6;
        color: #8b5cf6;
    }

        .promote-btn:hover {
            background: #8b5cf6;
            color: white;
        }

    .demote-btn {
        border-color: #f59e0b;
        color: #f59e0b;
    }

        .demote-btn:hover {
            background: #f59e0b;
            color: white;
        }

    .delete-btn {
        border-color: #ef4444;
        color: #ef4444;
    }

        .delete-btn:hover {
            background: #ef4444;
            color: white;
        }

    .action-icon {
        height: 0.875rem;
        width: 0.875rem;
    }

    /* Lapošana */
    .pagination-wrapper {
        padding: 1.5rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .pagination-info {
        font-size: 0.875rem;
        color: #64748b;
    }

    .pagination-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .pagination-link {
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        color: #475569;
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

        .pagination-link:hover:not(.disabled) {
            background: #f97316;
            color: white;
            border-color: #f97316;
        }

        .pagination-link.active {
            background: #f97316;
            color: white;
            border-color: #f97316;
        }

        .pagination-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

    /* Mobilais */
    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .users-table {
            font-size: 0.875rem;
        }

        .action-buttons {
            flex-direction: column;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .header-content {
            flex-direction: column;
        }

        .card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .search-wrapper {
            width: 100%;
        }

        .search-input-container {
            width: 100%;
        }

        .search-input {
            width: 100%;
        }

        .pagination-wrapper {
            flex-direction: column;
            align-items: flex-start;
        }

        .users-table-container {
            overflow-x: scroll;
        }

        .users-table {
            min-width: 800px;
        }
    }
</style>
