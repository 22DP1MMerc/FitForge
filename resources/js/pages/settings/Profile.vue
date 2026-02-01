<script setup lang="ts">
    import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
    import { ref, onMounted } from 'vue';
    import DeleteUser from '@/components/DeleteUser.vue';
    import InputError from '@/components/InputError.vue';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
    import { Separator } from '@/components/ui/separator';
    import { CheckCircle, Mail, User, ShieldAlert, Target, Plus, TrendingUp, Calendar, Award, Trash2, Edit, ChevronRight } from 'lucide-vue-next';
    import AppLayout from '@/layouts/AppLayout.vue';
    import axios from 'axios';

    interface Props {
        mustVerifyEmail: boolean;
        status?: string;
    }

    defineProps<Props>();

    const page = usePage();
    const user = page.props.auth.user;

    const form = useForm({
        name: user.name,
        email: user.email,
    });

    const submit = () => {
        form.patch(route('profile.update'), {
            preserveScroll: true,
        });
    };

    // Mērķu funkcionalitāte
    const goals = ref<any[]>([]);
    const loading = ref(false);
    const showGoalForm = ref(false);
    const goalForm = ref({
        title: '',
        description: '',
        type: 'workout',
        target_value: '',
        unit: '',
        deadline: ''
    });

    // Ielādē mērķus
    const loadGoals = async () => {
        try {
            loading.value = true;
            const response = await axios.get('/api/goals');
            goals.value = response.data;
        } catch (error) {
            console.error('Error loading goals:', error);
        } finally {
            loading.value = false;
        }
    };

    // Izveidot jaunu mērķi
    const createGoal = async () => {
        try {
            await axios.post('/api/goals', {
                ...goalForm.value,
                target_value: parseFloat(goalForm.value.target_value)
            });
            await loadGoals();
            showGoalForm.value = false;
            resetGoalForm();
        } catch (error) {
            console.error('Error creating goal:', error);
        }
    };

    // Atjaunināt mērķi
    const updateGoal = async (goalId: number, updates: any) => {
        try {
            await axios.put(`/api/goals/${goalId}`, updates);
            await loadGoals();
        } catch (error) {
            console.error('Error updating goal:', error);
        }
    };

    // Dzēst mērķi
    const deleteGoal = async (goalId: number) => {
        if (confirm('Vai tiešām vēlaties dzēst šo mērķi?')) {
            try {
                await axios.delete(`/api/goals/${goalId}`);
                await loadGoals();
            } catch (error) {
                console.error('Error deleting goal:', error);
            }
        }
    };

    const resetGoalForm = () => {
        goalForm.value = {
            title: '',
            description: '',
            type: 'workout',
            target_value: '',
            unit: '',
            deadline: ''
        };
    };

    // Aprēķina progresa procentu
    const calculateProgress = (goal: any) => {
        if (!goal.target_value || goal.target_value === 0) return 0;
        return Math.min(Math.round((goal.current_value / goal.target_value) * 100), 100);
    };

    // Atgriež mērķa tipa ikonu
    const getGoalTypeIcon = (type: string) => {
        switch (type) {
            case 'workout': return '💪';
            case 'weight': return '⚖️';
            case 'strength': return '🏋️';
            case 'endurance': return '🏃';
            default: return '🎯';
        }
    };

    // Atgriež mērķa tipa nosaukumu
    const getGoalTypeName = (type: string) => {
        switch (type) {
            case 'workout': return 'Treniņš';
            case 'weight': return 'Svars';
            case 'strength': return 'Spēks';
            case 'endurance': return 'Izturība';
            default: return 'Cits';
        }
    };

    // Atgriež mērķa tipa krāsu
    const getGoalTypeColor = (type: string) => {
        switch (type) {
            case 'workout': return '#3b82f6';
            case 'weight': return '#10b981';
            case 'strength': return '#f97316';
            case 'endurance': return '#8b5cf6';
            default: return '#6b7280';
        }
    };

    onMounted(() => {
        loadGoals();
    });
</script>

<template>
    <AppLayout>
        <Head title="Profila iestatījumi" />

        <div class="profile-page-container">
            <div class="container-wrapper">
                <!-- Header -->
                <div class="header-section">
                    <h1 class="main-title">
                        Profila iestatījumi
                    </h1>
                    <p class="description-text">
                        Pārvaldi savu konta informāciju un drošības iestatījumus
                    </p>
                </div>

                <div class="content-grid">
                    <!-- Left Column - Profile Info & Goals -->
                    <div class="left-column">
                        <!-- Profile Information Card -->
                        <Card class="profile-card">
                            <CardHeader class="card-header">
                                <div class="header-icon-wrapper">
                                    <div class="icon-circle">
                                        <User class="icon" />
                                    </div>
                                    <div>
                                        <CardTitle class="card-title">
                                            Profila informācija
                                        </CardTitle>
                                        <CardDescription class="card-description">
                                            Atjaunini savu personīgo informāciju
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent class="card-content">
                                <form @submit.prevent="submit" class="profile-form">
                                    <div class="form-fields">
                                        <div class="form-field">
                                            <Label for="name" class="field-label">
                                                Vārds
                                            </Label>
                                            <div class="input-wrapper">
                                                <Input id="name"
                                                       v-model="form.name"
                                                       required
                                                       autocomplete="name"
                                                       placeholder="Jūsu vārds"
                                                       :class="[
                                                        'custom-input',
                                                        form.errors.name ? 'input-error' : ''
                                                    ]" />
                                                <User class="input-icon" />
                                            </div>
                                            <InputError class="error-message" :message="form.errors.name" />
                                        </div>

                                        <div class="form-field">
                                            <Label for="email" class="field-label">
                                                E-pasta adrese
                                            </Label>
                                            <div class="input-wrapper">
                                                <Input id="email"
                                                       type="email"
                                                       v-model="form.email"
                                                       required
                                                       autocomplete="email"
                                                       placeholder="jūsu@epasts.com"
                                                       :class="[
                                                        'custom-input',
                                                        form.errors.email ? 'input-error' : ''
                                                    ]" />
                                                <Mail class="input-icon" />
                                            </div>
                                            <InputError class="error-message" :message="form.errors.email" />
                                        </div>
                                    </div>

                                    <!-- Email Verification Alert -->
                                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="verification-alert">
                                        <ShieldAlert class="alert-icon" />
                                        <div class="alert-description">
                                            Jūsu e-pasta adrese nav verificēta.
                                            <Link :href="route('verification.send')"
                                                  method="post"
                                                  as="button"
                                                  class="verification-link">
                                            Noklikšķini šeit, lai nosūtītu verifikācijas e-pastu vēlreiz.
                                            </Link>
                                        </div>
                                    </div>

                                    <div v-if="status === 'verification-link-sent'" class="success-alert">
                                        <CheckCircle class="success-icon" />
                                        <div class="success-description">
                                            Jauns verifikācijas saites e-pasts ir nosūtīts uz jūsu e-pasta adresi.
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <Button type="submit"
                                                :disabled="form.processing"
                                                class="submit-button">
                                            <span v-if="form.processing" class="loading-text">
                                                <svg class="loading-spinner" viewBox="0 0 24 24">
                                                    <circle class="spinner-background" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                                    <path class="spinner-foreground" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                </svg>
                                                Saglabā...
                                            </span>
                                            <span v-else class="button-text">
                                                <CheckCircle class="button-icon" />
                                                Saglabāt izmaiņas
                                            </span>
                                        </Button>

                                        <Transition enter-active-class="fade-in"
                                                    enter-from-class="fade-start"
                                                    enter-to-class="fade-end"
                                                    leave-active-class="fade-out"
                                                    leave-from-class="fade-end"
                                                    leave-to-class="fade-start">
                                            <p v-show="form.recentlySuccessful"
                                               class="success-message">
                                                <CheckCircle class="success-icon-small" />
                                                Izmaiņas saglabātas!
                                            </p>
                                        </Transition>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <!-- Goals Card -->
                        <Card class="goals-card">
                            <CardHeader class="card-header">
                                <div class="header-icon-wrapper">
                                    <div class="icon-circle goal-icon">
                                        <Target class="icon" />
                                    </div>
                                    <div>
                                        <CardTitle class="card-title">
                                            Mani mērķi
                                        </CardTitle>
                                        <CardDescription class="card-description">
                                            Sekoji saviem fitnesa mērķiem
                                        </CardDescription>
                                    </div>
                                </div>
                                <Button @click="showGoalForm = !showGoalForm"
                                        variant="outline"
                                        size="sm"
                                        class="add-goal-btn">
                                    <Plus class="add-icon" />
                                    Jauns mērķis
                                </Button>
                            </CardHeader>

                            <CardContent class="card-content">
                                <!-- Goal Creation Form -->
                                <div v-if="showGoalForm" class="goal-form-section">
                                    <div class="form-fields">
                                        <div class="form-field">
                                            <Label class="field-label">Mērķa nosaukums</Label>
                                            <Input v-model="goalForm.title"
                                                   placeholder="Piemēram: Sasniegt 100kg piegājienu"
                                                   class="custom-input" />
                                        </div>

                                        <div class="form-field">
                                            <Label class="field-label">Apraksts</Label>
                                            <Input v-model="goalForm.description"
                                                   placeholder="Īss apraksts par mērķi..."
                                                   class="custom-input" />
                                        </div>

                                        <div class="form-row">
                                            <div class="form-field">
                                                <Label class="field-label">Tips</Label>
                                                <select v-model="goalForm.type" class="select-input">
                                                    <option value="workout">Treniņš</option>
                                                    <option value="weight">Svars</option>
                                                    <option value="strength">Spēks</option>
                                                    <option value="endurance">Izturība</option>
                                                </select>
                                            </div>

                                            <div class="form-field">
                                                <Label class="field-label">Mērvienība</Label>
                                                <Input v-model="goalForm.unit"
                                                       placeholder="kg, treniņi, dienas..."
                                                       class="custom-input" />
                                            </div>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-field">
                                                <Label class="field-label">Mērķa vērtība</Label>
                                                <Input v-model="goalForm.target_value"
                                                       type="number"
                                                       placeholder="100"
                                                       class="custom-input" />
                                            </div>

                                            <div class="form-field">
                                                <Label class="field-label">Termiņš</Label>
                                                <Input v-model="goalForm.deadline"
                                                       type="date"
                                                       class="custom-input" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <Button @click="createGoal"
                                                class="submit-button">
                                            <Plus class="button-icon" />
                                            Izveidot mērķi
                                        </Button>
                                        <Button @click="showGoalForm = false"
                                                variant="outline">
                                            Atcelt
                                        </Button>
                                    </div>
                                </div>

                                <!-- Goals List -->
                                <div v-if="loading" class="loading-state">
                                    <div class="loading-spinner"></div>
                                    <p>Ielādē mērķus...</p>
                                </div>

                                <div v-else-if="goals.length === 0" class="empty-state">
                                    <Target class="empty-icon" />
                                    <p class="empty-title">Vēl nav mērķu</p>
                                    <p class="empty-description">Izveido pirmo mērķi, lai sāktu progresu!</p>
                                    <Button @click="showGoalForm = true"
                                            class="submit-button">
                                        <Plus class="button-icon" />
                                        Izveidot pirmo mērķi
                                    </Button>
                                </div>

                                <div v-else class="goals-list">
                                    <div v-for="goal in goals" :key="goal.id" class="goal-item">
                                        <div class="goal-content">
                                            <div class="goal-header">
                                                <div class="goal-type-badge" :style="{ backgroundColor: getGoalTypeColor(goal.type) + '20', borderColor: getGoalTypeColor(goal.type) + '40' }">
                                                    <span class="goal-type-icon">{{ getGoalTypeIcon(goal.type) }}</span>
                                                    <span class="goal-type-name">{{ getGoalTypeName(goal.type) }}</span>
                                                </div>
                                                <div class="goal-actions">
                                                    <Button @click="updateGoal(goal.id, { completed: !goal.completed })"
                                                            size="sm"
                                                            :variant="goal.completed ? 'default' : 'outline'"
                                                            class="complete-btn">
                                                        {{ goal.completed ? '✓ Pabeigts' : 'Atzīmēt' }}
                                                    </Button>
                                                </div>
                                            </div>

                                            <h4 class="goal-title">{{ goal.title }}</h4>
                                            <p v-if="goal.description" class="goal-description">
                                                {{ goal.description }}
                                            </p>

                                            <div class="goal-progress">
                                                <div class="progress-header">
                                                    <span class="progress-text">
                                                        {{ goal.current_value || 0 }}/{{ goal.target_value }} {{ goal.unit }}
                                                    </span>
                                                    <span class="progress-percentage" :style="{ color: getGoalTypeColor(goal.type) }">
                                                        {{ calculateProgress(goal) }}%
                                                    </span>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill"
                                                         :style="{
                                                             width: calculateProgress(goal) + '%',
                                                             backgroundColor: getGoalTypeColor(goal.type)
                                                         }"
                                                         :class="{ 'completed': goal.completed }"></div>
                                                </div>
                                            </div>

                                            <div class="goal-footer">
                                                <div v-if="goal.deadline" class="goal-deadline">
                                                    <Calendar class="deadline-icon" />
                                                    <span>Termiņš: {{ new Date(goal.deadline).toLocaleDateString('lv-LV') }}</span>
                                                </div>
                                                <Button @click="deleteGoal(goal.id)"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="delete-btn">
                                                    <Trash2 class="delete-icon" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Delete Account Card -->
                        <DeleteUser />
                    </div>

                    <!-- Right Column - Account Status & Stats -->
                    <div class="right-column">
                        <!-- Account Status Card -->
                        <Card class="status-card">
                            <CardHeader>
                                <CardTitle class="status-title">
                                    Konta statuss
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="status-content">
                                <div class="status-items">
                                    <div class="status-item">
                                        <span class="item-label">E-pasta verifikācija</span>
                                        <span :class="['status-badge', user.email_verified_at ? 'verified' : 'not-verified']">
                                            {{ user.email_verified_at ? 'Verificēts' : 'Nav verificēts' }}
                                        </span>
                                    </div>

                                    <div class="status-item">
                                        <span class="item-label">Konta statuss</span>
                                        <span class="status-badge active">
                                            Aktīvs
                                        </span>
                                    </div>

                                    <div class="status-item">
                                        <span class="item-label">Reģistrācijas datums</span>
                                        <span class="date-text">
                                            {{ new Date(user.created_at).toLocaleDateString('lv-LV') }}
                                        </span>
                                    </div>
                                </div>

                                <Separator class="divider" />

                                <!-- Goals Statistics -->
                                <div class="goals-stats">
                                    <h4 class="stats-title">
                                        <TrendingUp class="stats-icon" />
                                        Mērķu statistika
                                    </h4>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <div class="stat-value">{{ goals.length }}</div>
                                            <div class="stat-label">Kopā mērķi</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">{{ goals.filter(g => g.completed).length }}</div>
                                            <div class="stat-label">Pabeigti</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value">
                                                {{
                                                goals.length > 0 ?
                                                Math.round((goals.filter(g => g.completed).length / goals.length) * 100) : 0
                                                }}%
                                            </div>
                                            <div class="stat-label">Veiksmīgība</div>
                                        </div>
                                    </div>
                                </div>

                                <Separator class="divider" />

                                <div class="quick-actions">
                                    <h4 class="actions-title">Ātrās darbības</h4>
                                    <div class="actions-buttons">
                                        <Button variant="outline" class="action-button">
                                            <Mail class="action-icon" />
                                            Mainīt e-pastu
                                        </Button>
                                        <Button variant="outline" class="action-button">
                                            <ShieldAlert class="action-icon" />
                                            Mainīt paroli
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Recent Goals Card -->
                        <Card class="recent-goals-card">
                            <CardHeader>
                                <CardTitle class="recent-goals-title">
                                    <Award class="recent-icon" />
                                    Nesen pabeigtie
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="recent-goals-content">
                                <div v-if="goals.filter(g => g.completed).length > 0" class="recent-list">
                                    <div v-for="goal in goals.filter(g => g.completed).slice(0, 3)"
                                         :key="goal.id"
                                         class="recent-item">
                                        <div class="recent-icon-wrapper" :style="{ backgroundColor: getGoalTypeColor(goal.type) + '20' }">
                                            <span class="recent-type-icon">{{ getGoalTypeIcon(goal.type) }}</span>
                                        </div>
                                        <div class="recent-details">
                                            <div class="recent-name">{{ goal.title }}</div>
                                            <div class="recent-date">
                                                Pabeigts {{ new Date(goal.updated_at).toLocaleDateString('lv-LV') }}
                                            </div>
                                        </div>
                                        <ChevronRight class="chevron-icon" />
                                    </div>
                                </div>
                                <div v-else class="no-recent">
                                    <p>Vēl nav pabeigto mērķu</p>
                                    <p class="hint">Sasniedz savus mērķus un iegūsti sasniegumus!</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Tips Card -->
                        <Card class="tips-card">
                            <CardHeader>
                                <CardTitle class="tips-title">
                                    💡 Padomi
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="tips-content">
                                <ul class="tips-list">
                                    <li class="tip-item">
                                        <div class="tip-bullet"></div>
                                        <span>Izveido īstermiņa un ilgtermiņa mērķus</span>
                                    </li>
                                    <li class="tip-item">
                                        <div class="tip-bullet"></div>
                                        <span>Regulāri atjaunini progresu</span>
                                    </li>
                                    <li class="tip-item">
                                        <div class="tip-bullet"></div>
                                        <span>Celebrē katru sasniegumu</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    /* Main Container - saglabā oriģinālo stilu */
    .profile-page-container {
        min-height: 100vh;
        background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
    }

    .container-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* Header Section - saglabā oriģinālo */
    .header-section {
        margin-bottom: 2rem;
        text-align: center;
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
        margin: 0 auto;
        line-height: 1.6;
    }

    /* Content Grid - saglabā oriģinālo */
    .content-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 1024px) {
        .content-grid {
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
    }

    /* Left Column - saglabā oriģinālo */
    .left-column {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Profile Card - saglabā oriģinālo */
    .profile-card {
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: white;
        overflow: hidden;
    }

    /* Goals Card - pielāgo oriģinālajam stiliam */
    .goals-card {
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: white;
        overflow: hidden;
    }

    .goal-icon {
        background-color: #f0f9ff;
    }

        .goal-icon .icon {
            color: #0ea5e9;
        }

    /* Card Header - oriģinālais stils */
    .card-header {
        padding: 1.5rem 1.5rem 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .header-icon-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .icon-circle {
        padding: 0.75rem;
        border-radius: 50%;
        background-color: #fff7ed;
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
        margin: 0;
    }

    .card-description {
        color: #64748b;
        font-size: 0.95rem;
        margin-top: 0.25rem;
    }

    /* Add Goal Button - pielāgots oriģinālam */
    .add-goal-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .add-icon {
        height: 1rem;
        width: 1rem;
    }

    .card-content {
        padding: 1.5rem;
    }

    /* Goal Form Section - pielāgots oriģinālam */
    .goal-form-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    /* Select Input - pielāgots custom-input stilam */
    .select-input {
        width: 100%;
        padding: 0.5rem 1rem;
        height: 2.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        background: white;
        font-size: 0.95rem;
        color: #1e293b;
        transition: all 0.2s ease;
    }

        .select-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

    /* Loading State - pielāgots oriģinālam */
    .loading-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        color: #64748b;
    }

        .loading-state .loading-spinner {
            height: 3rem;
            width: 3rem;
            border: 3px solid #e2e8f0;
            border-top-color: #f97316;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

    /* Empty State - pielāgots oriģinālam */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #64748b;
    }

    .empty-icon {
        height: 3rem;
        width: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.5rem;
    }

    .empty-description {
        color: #64748b;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    /* Goals List - pielāgots oriģinālajam */
    .goals-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .goal-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1.25rem;
        transition: all 0.2s;
    }

        .goal-item:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

    .goal-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .goal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .goal-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        border: 1px solid;
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
    }

    .goal-type-icon {
        font-size: 0.875rem;
    }

    .goal-type-name {
        font-size: 0.75rem;
        font-weight: 500;
    }

    .goal-actions {
        display: flex;
        gap: 0.5rem;
    }

    .complete-btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        height: auto;
    }

    .goal-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .goal-description {
        color: #64748b;
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.5;
    }

    /* Progress Bar - pielāgots oriģinālam */
    .goal-progress {
        margin: 0.5rem 0;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .progress-text {
        font-size: 0.875rem;
        color: #475569;
    }

    .progress-percentage {
        font-size: 0.875rem;
        font-weight: 600;
    }

    .progress-bar {
        height: 0.5rem;
        background: #e2e8f0;
        border-radius: 9999px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }

        .progress-fill.completed {
            background: linear-gradient(90deg, #10b981, #34d399) !important;
        }

    /* Goal Footer */
    .goal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
    }

    .goal-deadline {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: #64748b;
    }

    .deadline-icon {
        height: 0.875rem;
        width: 0.875rem;
        color: #9ca3af;
    }

    .delete-btn {
        padding: 0.25rem;
        height: auto;
        color: #9ca3af;
    }

    .delete-icon {
        height: 0.875rem;
        width: 0.875rem;
    }

    /* Stats Card - pielāgots oriģinālajam Status Card */
    .goals-stats {
        margin-bottom: 1.5rem;
    }

    .stats-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
    }

    .stats-icon {
        height: 1.25rem;
        width: 1.25rem;
        color: #0ea5e9;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    .stat-item {
        text-align: center;
        background: #f8fafc;
        padding: 1rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0ea5e9;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    /* Recent Goals Card - pielāgots oriģinālajam */
    .recent-goals-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }

    .recent-goals-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .recent-icon {
        height: 1.25rem;
        width: 1.25rem;
        color: #f97316;
    }

    .recent-goals-content {
        padding: 1.5rem;
    }

    .recent-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .recent-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        transition: all 0.2s;
        cursor: pointer;
    }

        .recent-item:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

    .recent-icon-wrapper {
        padding: 0.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .recent-type-icon {
        font-size: 1rem;
    }

    .recent-details {
        flex: 1;
    }

    .recent-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 0.125rem;
    }

    .recent-date {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .chevron-icon {
        height: 1rem;
        width: 1rem;
        color: #9ca3af;
    }

    .no-recent {
        text-align: center;
        padding: 1.5rem;
        color: #6b7280;
    }

        .no-recent .hint {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }

    /* Status Badges - oriģinālais stils */
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

        .status-badge.verified {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.not-verified {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge.active {
            background: #dbeafe;
            color: #1e40af;
        }

    /* Divider - oriģinālais stils */
    .divider {
        margin: 1.5rem 0;
        background-color: #e2e8f0;
    }

    /* Tips Card - oriģinālais stils */
    .tips-card {
        background: linear-gradient(135deg, #ffedd5, #fed7aa);
        border: 2px solid #fdba74;
        border-radius: 1rem;
    }

    .tips-title {
        color: #9a3412;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .tips-content {
        padding: 1.5rem;
    }

    .tips-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .tip-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .tip-bullet {
        flex-shrink: 0;
        margin-top: 0.375rem;
        height: 0.75rem;
        width: 0.75rem;
        border-radius: 50%;
        background-color: #f97316;
    }

    .tip-item span {
        color: #7c2d12;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    /* Custom Input - oriģinālais stils */
    .custom-input {
        width: 100%;
        padding: 0.5rem 1rem;
        height: 2.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        background: white;
        font-size: 0.95rem;
        color: #1e293b;
        transition: all 0.2s ease;
    }

        .custom-input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

    /* Submit Button - oriģinālais stils */
    .submit-button {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        padding: 0.5rem 1.5rem;
        height: 2.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

        .submit-button:hover:not(:disabled) {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        .submit-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

    /* Animation */
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .goal-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .goal-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .goal-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 640px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .stat-item {
            padding: 0.75rem;
        }
    }
</style>
