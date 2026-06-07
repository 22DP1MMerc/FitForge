<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { CheckCircle, Mail, User, ShieldAlert, Target, Plus, TrendingUp, Calendar, Award, Trash2, Edit, ChevronRight, Dumbbell, Flame, Clock, Sparkles, Zap, Crown, Medal, Loader2, Eye, EyeOff, Lock, Settings, LogOut } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

interface Exercise {
    id: number;
    name: string;
    muscle_group: string;
}

interface Goal {
    id: number;
    user_id: number;
    title: string;
    description: string | null;
    type: 'workout' | 'strength' | 'endurance';
    exercise_id: number | null;
    exercise: Exercise | null;
    target_value: number;
    current_value: number;
    unit: string | null;
    deadline: string | null;
    completed: boolean;
    created_at: string;
    updated_at: string;
}

interface GoalForm {
    title: string;
    description: string;
    type: 'workout' | 'strength' | 'endurance';
    exercise_id: string;
    target_value: string;
    unit: string;
    deadline: string;
}

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = (page.props as any).auth.user;

// Profile form
const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordStrength = computed(() => {
    const p = passwordForm.password;
    return {
        minLength: p.length >= 8,
        hasUppercase: /[A-Z]/.test(p),
        hasNumberOrSymbol: /[0-9!@#$%^&*(),.?":{}|<>]/.test(p),
    };
});

const isPasswordStrong = computed(() =>
    passwordStrength.value.minLength &&
    passwordStrength.value.hasUppercase &&
    passwordStrength.value.hasNumberOrSymbol
);

const updatePassword = () => {
    passwordForm.clearErrors();
    if (!passwordForm.current_password) {
        passwordForm.setError('current_password', 'Ievadi pašreizējo paroli');
        return;
    }
    if (passwordForm.password.length > 0 && !isPasswordStrong.value) {
        passwordForm.setError('password', 'Parolei jābūt vismaz 8 rakstzīmēm, jāsatur lielais burts un cipars vai simbols');
        return;
    }
    if (passwordForm.password !== passwordForm.password_confirmation) {
        passwordForm.setError('password_confirmation', 'Paroles nesakrīt');
        return;
    }
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset('current_password', 'password', 'password_confirmation');
            showPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
    });
};

// Goals functionality
const goals = ref<Goal[]>([]);
const loading = ref(false);
const showGoalForm = ref(false);
const processingGoalId = ref<number | null>(null);
const errorMessage = ref<string | null>(null);
const editingGoal = ref<Goal | null>(null);
const goalFormError = ref('');
const savingGoal = ref(false);
const deleteConfirmId = ref<number | null>(null);
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
    notification.value = { message, type };
    setTimeout(() => { notification.value = null; }, 3500);
};

const goalForm = ref<GoalForm>({
    title: '',
    description: '',
    type: 'workout',
    exercise_id: '',
    target_value: '',
    unit: '',
    deadline: '',
});

// Strength exercises for the goal form picker
const strengthExercises = ref<Exercise[]>([]);

const loadStrengthExercises = async () => {
    if (strengthExercises.value.length) return;
    try {
        const res = await axios.get('/api/exercises/strength');
        strengthExercises.value = res.data;
    } catch {
        // silently ignore — picker just stays empty
    }
};

// Group strength exercises by muscle group for the optgroup picker
const groupedStrengthExercises = computed(() => {
    const map = new Map<string, Exercise[]>();
    for (const ex of strengthExercises.value) {
        const g = ex.muscle_group || 'Citi';
        if (!map.has(g)) map.set(g, []);
        map.get(g)!.push(ex);
    }
    return Array.from(map.entries()).map(([label, exercises]) => ({ label, exercises }));
});

// Auto-set sensible unit when goal type changes
watch(() => goalForm.value.type, (type) => {
    const defaults: Record<string, string> = {
        workout: 'treniņi',
        strength: 'kg',
        endurance: 'min',
    };
    goalForm.value.unit = defaults[type] ?? '';
    if (type !== 'strength') goalForm.value.exercise_id = '';
    if (type === 'strength') loadStrengthExercises();
});

// Computed stats
const totalGoals = computed(() => goals.value.length);
const completedGoals = computed(() => goals.value.filter(g => g.completed).length);
const completionRate = computed(() => totalGoals.value > 0 ? Math.round((completedGoals.value / totalGoals.value) * 100) : 0);
const recentCompleted = computed(() => goals.value.filter(g => g.completed).slice(0, 3));
const inProgressGoals = computed(() => goals.value.filter(g => !g.completed));

// Load goals
const loadGoals = async () => {
    try {
        loading.value = true;
        errorMessage.value = null;
        const response = await axios.get('/api/goals');
        goals.value = response.data;
    } catch (error: any) {
        console.error('Error loading goals:', error);
        errorMessage.value = 'Neizdevās ielādēt mērķus. Lūdzu, mēģiniet vēlreiz.';
    } finally {
        loading.value = false;
    }
};

// Create or update goal
const saveGoal = async () => {
    goalFormError.value = '';

    if (!goalForm.value.title.trim()) {
        goalFormError.value = 'Lūdzu, ievadiet mērķa nosaukumu';
        return;
    }
    if (goalForm.value.type === 'strength' && !goalForm.value.exercise_id) {
        goalFormError.value = 'Spēka mērķim jāizvēlas vingrinājums';
        return;
    }
    if (!goalForm.value.target_value || parseFloat(goalForm.value.target_value) <= 0) {
        goalFormError.value = 'Lūdzu, ievadiet derīgu mērķa vērtību (lielāku par 0)';
        return;
    }

    try {
        savingGoal.value = true;
        const isEditing = !!editingGoal.value;

        const payload: Record<string, any> = {
            title: goalForm.value.title.trim(),
            description: goalForm.value.description.trim() || null,
            type: goalForm.value.type,
            exercise_id: goalForm.value.exercise_id ? parseInt(goalForm.value.exercise_id) : null,
            target_value: parseFloat(goalForm.value.target_value),
            unit: goalForm.value.unit.trim() || null,
            deadline: goalForm.value.deadline || null,
        };

        if (editingGoal.value) {
            await axios.put(`/api/goals/${editingGoal.value.id}`, payload);
        } else {
            await axios.post('/api/goals', payload);
        }

        await loadGoals();
        showGoalForm.value = false;
        resetGoalForm();
        showNotification(isEditing ? 'Mērķis veiksmīgi atjaunināts!' : 'Mērķis veiksmīgi izveidots!');
    } catch (error: any) {
        console.error('Error saving goal:', error);
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            goalFormError.value = (Object.values(errors) as string[][]).flat().join(', ');
        } else {
            goalFormError.value = 'Neizdevās saglabāt mērķi. Lūdzu, mēģiniet vēlreiz.';
        }
    } finally {
        savingGoal.value = false;
    }
};

const editGoal = (goal: Goal) => {
    editingGoal.value = goal;
    goalForm.value = {
        title: goal.title,
        description: goal.description || '',
        type: goal.type,
        exercise_id: goal.exercise_id?.toString() || '',
        target_value: goal.target_value.toString(),
        unit: goal.unit || '',
        deadline: goal.deadline || '',
    };
    showGoalForm.value = true;
};

const toggleGoalCompletion = async (goalId: number) => {
    const goal = goals.value.find(g => g.id === goalId);
    if (!goal) return;

    try {
        processingGoalId.value = goalId;
        await axios.put(`/api/goals/${goalId}`, { completed: !goal.completed });
        await loadGoals();
    } catch (error: any) {
        console.error('Error toggling goal completion:', error);
        showNotification('Neizdevās atjaunināt mērķa statusu', 'error');
    } finally {
        processingGoalId.value = null;
    }
};

const deleteGoal = async (goalId: number) => {
    try {
        processingGoalId.value = goalId;
        deleteConfirmId.value = null;
        await axios.delete(`/api/goals/${goalId}`);
        await loadGoals();
        showNotification('Mērķis veiksmīgi dzēsts!');
    } catch (error: any) {
        console.error('Error deleting goal:', error);
        showNotification('Neizdevās dzēst mērķi', 'error');
    } finally {
        processingGoalId.value = null;
    }
};

const resetGoalForm = () => {
    editingGoal.value = null;
    goalForm.value = {
        title: '',
        description: '',
        type: 'workout',
        exercise_id: '',
        target_value: '',
        unit: '',
        deadline: '',
    };
};

const cancelGoalForm = () => {
    showGoalForm.value = false;
    goalFormError.value = '';
    resetGoalForm();
};

const goalTypes = [
    { value: 'workout',   label: 'Treniņš',  emoji: '💪' },
    { value: 'strength',  label: 'Spēks',    emoji: '🏋️' },
    { value: 'endurance', label: 'Izturība', emoji: '🏃' },
] as const;

// Get goal type config
const getGoalTypeConfig = (type: string) => {
    const configs = {
        workout: { icon: Dumbbell, name: 'Treniņš', color: '#3b82f6', bg: '#eff6ff', gradient: 'from-blue-500 to-blue-600' },
        strength: { icon: Zap, name: 'Spēks', color: '#f97316', bg: '#fff7ed', gradient: 'from-orange-500 to-orange-600' },
        endurance: { icon: Flame, name: 'Izturība', color: '#8b5cf6', bg: '#f5f3ff', gradient: 'from-violet-500 to-violet-600' }
    };
    return configs[type as keyof typeof configs] || configs.workout;
};

// Get progress percentage
const getProgressPercentage = (goal: Goal) => {
    if (goal.completed) return 100;
    const progress = (goal.current_value / goal.target_value) * 100;
    return Math.min(Math.round(progress), 100);
};

onMounted(() => {
    loadGoals();
});
</script>

<template>
    <AppLayout>
        <Head title="Profila iestatījumi" />

        <!-- Notification Toast -->
        <Transition name="toast">
            <div v-if="notification" class="notification-toast" :class="notification.type">
                <CheckCircle v-if="notification.type === 'success'" :size="18" />
                <ShieldAlert v-else :size="18" />
                <span>{{ notification.message }}</span>
            </div>
        </Transition>

        <div class="profile-page">
            <!-- Animated Background -->
            <div class="animated-bg">
                <div class="gradient-sphere sphere-1"></div>
                <div class="gradient-sphere sphere-2"></div>
                <div class="gradient-sphere sphere-3"></div>
            </div>

            <div class="container">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-badge">
                        <Sparkles class="badge-icon" />
                        <span>Personīgais profils</span>
                    </div>
                    <h1 class="hero-title">
                        Sveiks atpakaļ, <span class="highlight">{{ user.name.split(' ')[0] }}</span>!
                    </h1>
                    <p class="hero-description">
                        Pārvaldi savu kontu, seko līdzi mērķiem un uzraugi savu progresu vienuviet.
                    </p>
                </div>

                <div class="content-grid">
                    <!-- Left Column - Main Settings -->
                    <div class="left-column">
                        <!-- Profile Card -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon profile-icon">
                                        <User />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Profila informācija</h2>
                                        <p class="card-subtitle">Atjaunini savus personas datus</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <form @submit.prevent="submit" class="form-stack">
                                    <div class="form-group">
                                        <Label class="form-label">Vārds</Label>
                                        <div class="input-group">
                                            <User class="input-icon" />
                                            <Input v-model="form.name" required placeholder="Tavs vārds" :class="{ 'error': form.errors.name }" />
                                        </div>
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <div class="form-group">
                                        <Label class="form-label">E-pasta adrese</Label>
                                        <div class="input-group">
                                            <Mail class="input-icon" />
                                            <Input v-model="form.email" type="email" required placeholder="tavs@epasts.lv" :class="{ 'error': form.errors.email }" />
                                        </div>
                                        <InputError :message="form.errors.email" />
                                    </div>

                                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="alert-warning">
                                        <ShieldAlert class="alert-icon" />
                                        <div>
                                            <p class="alert-title">E-pasts nav verificēts</p>
                                            <p class="alert-text">
                                                Lūdzu, verificē savu e-pasta adresi.
                                                <Link :href="route('verification.send')" method="post" as="button" class="alert-link">
                                                Nosūtīt verifikāciju vēlreiz
                                                </Link>
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="status === 'verification-link-sent'" class="alert-success">
                                        <CheckCircle class="alert-icon" />
                                        <div>
                                            <p class="alert-title">Verifikācija nosūtīta!</p>
                                            <p class="alert-text">Jauns verifikācijas e-pasts ir nosūtīts uz tavu adresi.</p>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <Button type="submit" :disabled="form.processing" class="btn-primary">
                                            <Loader2 v-if="form.processing" class="btn-icon animate-spin" />
                                            <CheckCircle v-else class="btn-icon" />
                                            {{ form.processing ? 'Saglabā...' : 'Saglabāt izmaiņas' }}
                                        </Button>
                                        <Transition name="fade">
                                            <div v-show="form.recentlySuccessful" class="success-badge">
                                                <CheckCircle class="success-icon" />
                                                <span>Saglabāts!</span>
                                            </div>
                                        </Transition>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Password Card -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon password-icon">
                                        <Lock />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Drošība</h2>
                                        <p class="card-subtitle">Maini paroli un uzlabo konta drošību</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <form @submit.prevent="updatePassword" class="form-stack">
                                    <div class="form-group">
                                        <Label class="form-label">Pašreizējā parole</Label>
                                        <div class="input-group">
                                            <Lock class="input-icon" />
                                            <Input :type="showPassword ? 'text' : 'password'" v-model="passwordForm.current_password" required placeholder="Ievadi pašreizējo paroli" />
                                            <button type="button" @click="showPassword = !showPassword" class="toggle-password">
                                                <EyeOff v-if="showPassword" :size="18" />
                                                <Eye v-else :size="18" />
                                            </button>
                                        </div>
                                        <InputError :message="passwordForm.errors.current_password" />
                                    </div>

                                    <div class="form-group">
                                        <Label class="form-label">Jaunā parole</Label>
                                        <div class="input-group">
                                            <Lock class="input-icon" />
                                            <Input :type="showNewPassword ? 'text' : 'password'" v-model="passwordForm.password" required placeholder="Ievadi jauno paroli" />
                                            <button type="button" @click="showNewPassword = !showNewPassword" class="toggle-password">
                                                <EyeOff v-if="showNewPassword" :size="18" />
                                                <Eye v-else :size="18" />
                                            </button>
                                        </div>
                                        <div v-if="passwordForm.password.length > 0" class="pw-rules">
                                            <div class="pw-rule" :class="{ met: passwordStrength.minLength }">
                                                <span class="pw-dot"></span> Vismaz 8 rakstzīmes
                                            </div>
                                            <div class="pw-rule" :class="{ met: passwordStrength.hasUppercase }">
                                                <span class="pw-dot"></span> Lielais burts (A–Z)
                                            </div>
                                            <div class="pw-rule" :class="{ met: passwordStrength.hasNumberOrSymbol }">
                                                <span class="pw-dot"></span> Cipars vai simbols
                                            </div>
                                        </div>
                                        <InputError :message="passwordForm.errors.password" />
                                    </div>

                                    <div class="form-group">
                                        <Label class="form-label">Apstiprini jauno paroli</Label>
                                        <div class="input-group">
                                            <Lock class="input-icon" />
                                            <Input :type="showConfirmPassword ? 'text' : 'password'" v-model="passwordForm.password_confirmation" required placeholder="Apstiprini jauno paroli" />
                                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="toggle-password">
                                                <EyeOff v-if="showConfirmPassword" :size="18" />
                                                <Eye v-else :size="18" />
                                            </button>
                                        </div>
                                        <p v-if="passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation && !passwordForm.errors.password_confirmation" class="password-hint mismatch">
                                            Paroles nesakrīt
                                        </p>
                                        <p v-else-if="passwordForm.password_confirmation && passwordForm.password === passwordForm.password_confirmation" class="password-hint match">
                                            Paroles sakrīt
                                        </p>
                                        <InputError :message="passwordForm.errors.password_confirmation" />
                                    </div>

                                    <div class="form-actions">
                                        <Button type="submit" :disabled="passwordForm.processing" class="btn-primary btn-secondary">
                                            <Loader2 v-if="passwordForm.processing" class="btn-icon animate-spin" />
                                            <ShieldAlert v-else class="btn-icon" />
                                            {{ passwordForm.processing ? 'Atjaunina...' : 'Mainīt paroli' }}
                                        </Button>
                                        <Transition name="fade">
                                            <div v-show="passwordForm.recentlySuccessful" class="success-badge">
                                                <CheckCircle class="success-icon" />
                                                <span>Parole nomainīta!</span>
                                            </div>
                                        </Transition>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Goals Card -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon goals-icon">
                                        <Target />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Mani mērķi</h2>
                                        <p class="card-subtitle">Izvirzi mērķus un seko progresam</p>
                                    </div>
                                </div>
                                <button @click="showGoalForm = true; resetGoalForm()" class="btn-add">
                                    <Plus :size="18" />
                                    <span>Jauns mērķis</span>
                                </button>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <!-- Goal Form Modal — teleported to body to escape card transform stacking context -->
                                <Teleport to="body">
                                    <Transition name="modal-anim">
                                        <div v-if="showGoalForm" class="gm-overlay" @click.self="cancelGoalForm">
                                            <div class="gm-panel">
                                                <!-- Header -->
                                                <div class="gm-header">
                                                    <div class="gm-header-icon" :class="`gm-type-${goalForm.type}`">
                                                        <Target :size="20" />
                                                    </div>
                                                    <div class="gm-header-text">
                                                        <h3 class="gm-title">{{ editingGoal ? 'Rediģēt mērķi' : 'Jauns mērķis' }}</h3>
                                                        <p class="gm-subtitle">{{ editingGoal ? 'Atjaunini mērķa informāciju' : 'Izvirzi jaunu fitnesa mērķi' }}</p>
                                                    </div>
                                                    <button @click="cancelGoalForm" class="gm-close" aria-label="Aizvērt">
                                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M13.5 4.5L4.5 13.5M4.5 4.5L13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                    </button>
                                                </div>

                                                <!-- Error -->
                                                <div v-if="goalFormError" class="gm-error">
                                                    <ShieldAlert :size="15" />
                                                    <span>{{ goalFormError }}</span>
                                                </div>

                                                <!-- Body -->
                                                <div class="gm-body">
                                                    <!-- Type selector — visual pills -->
                                                    <div class="gm-field">
                                                        <label class="gm-label">Tips</label>
                                                        <div class="gm-type-pills">
                                                            <button type="button" v-for="t in goalTypes" :key="t.value"
                                                                @click="goalForm.type = t.value"
                                                                class="gm-pill"
                                                                :class="{ active: goalForm.type === t.value, [`pill-${t.value}`]: true }">
                                                                <span>{{ t.emoji }}</span>
                                                                <span>{{ t.label }}</span>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Title -->
                                                    <div class="gm-field">
                                                        <label class="gm-label">Nosaukums <span class="gm-required">*</span></label>
                                                        <input v-model="goalForm.title" class="gm-input" placeholder="Piemēram: Noskriet 5 km" @input="goalFormError = ''" />
                                                    </div>

                                                    <!-- Exercise picker — strength only -->
                                                    <div v-if="goalForm.type === 'strength'" class="gm-field">
                                                        <label class="gm-label">Vingrinājums <span class="gm-required">*</span></label>
                                                        <select v-model="goalForm.exercise_id" class="gm-input gm-select" @change="goalFormError = ''">
                                                            <option value="">— izvēlies vingrinājumu —</option>
                                                            <optgroup v-for="group in groupedStrengthExercises" :key="group.label" :label="group.label">
                                                                <option v-for="ex in group.exercises" :key="ex.id" :value="ex.id.toString()">{{ ex.name }}</option>
                                                            </optgroup>
                                                        </select>
                                                    </div>

                                                    <!-- Auto-track info banner -->
                                                    <div class="gm-auto-info">
                                                        <Zap :size="14" />
                                                        <span v-if="goalForm.type === 'workout'">Automātiski atjaunojas pēc katra pabeigta treniņa</span>
                                                        <span v-else-if="goalForm.type === 'strength'">Atjaunojas kad uzstādīts jauns personīgais rekords izvēlētajā vingrinājumā</span>
                                                        <span v-else-if="goalForm.type === 'endurance'">Uzkrājas kopējais kardio laiks no visiem treniņiem</span>
                                                    </div>

                                                    <!-- Description -->
                                                    <div class="gm-field">
                                                        <label class="gm-label">Apraksts <span class="gm-optional">— pēc izvēles</span></label>
                                                        <textarea v-model="goalForm.description" class="gm-textarea" rows="2" placeholder="Īss apraksts par mērķi..."></textarea>
                                                    </div>

                                                    <!-- Value + Unit -->
                                                    <div class="gm-row">
                                                        <div class="gm-field">
                                                            <label class="gm-label">
                                                                Mērķa vērtība <span class="gm-required">*</span>
                                                            </label>
                                                            <input v-model="goalForm.target_value" type="number" min="0.01" step="any" class="gm-input"
                                                                :placeholder="goalForm.type === 'workout' ? '50' : goalForm.type === 'endurance' ? '120' : '100'"
                                                                @input="goalFormError = ''" />
                                                        </div>
                                                        <div class="gm-field">
                                                            <label class="gm-label">Mērvienība</label>
                                                            <select v-model="goalForm.unit" class="gm-input gm-select">
                                                                <template v-if="goalForm.type === 'workout'">
                                                                    <option value="treniņi">treniņi</option>
                                                                </template>
                                                                <template v-else-if="goalForm.type === 'strength'">
                                                                    <option value="kg">kg</option>
                                                                    <option value="lbs">lbs</option>
                                                                </template>
                                                                <template v-else-if="goalForm.type === 'endurance'">
                                                                    <option value="min">min</option>
                                                                    <option value="km">km</option>
                                                                    <option value="soļi">soļi</option>
                                                                </template>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Deadline -->
                                                    <div class="gm-field">
                                                        <label class="gm-label">Termiņš <span class="gm-optional">— pēc izvēles</span></label>
                                                        <input v-model="goalForm.deadline" type="date" class="gm-input" />
                                                    </div>
                                                </div>

                                                <!-- Footer -->
                                                <div class="gm-footer">
                                                    <button @click="cancelGoalForm" class="gm-btn-cancel">Atcelt</button>
                                                    <button @click="saveGoal" :disabled="savingGoal" class="gm-btn-save">
                                                        <Loader2 v-if="savingGoal" :size="16" class="animate-spin" />
                                                        {{ savingGoal ? 'Saglabā...' : (editingGoal ? 'Saglabāt izmaiņas' : 'Izveidot mērķi') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </Teleport>

                                <!-- Loading State -->
                                <div v-if="loading && !showGoalForm" class="loading-state">
                                    <div class="loading-ring"></div>
                                    <p>Ielādē mērķus...</p>
                                </div>

                                <!-- Error Message -->
                                <div v-if="errorMessage" class="error-message">
                                    <ShieldAlert :size="18" />
                                    <span>{{ errorMessage }}</span>
                                </div>

                                <!-- Empty State -->
                                <div v-else-if="goals.length === 0" class="empty-state">
                                    <div class="empty-icon-wrapper">
                                        <Target class="empty-icon" />
                                    </div>
                                    <h3>Nav izveidots neviens mērķis</h3>
                                    <p>Sāc savu fitnesa ceļojumu ar pirmo mērķi!</p>
                                    <button @click="showGoalForm = true; resetGoalForm()" class="btn-primary">
                                        <Plus :size="18" />
                                        Izveidot pirmo mērķi
                                    </button>
                                </div>

                                <!-- Goals List -->
                                <div v-else class="goals-list">
                                    <div v-for="goal in goals" :key="goal.id" class="goal-card">
                                        <div class="goal-header">
                                            <div class="goal-type" :style="{ background: getGoalTypeConfig(goal.type).bg }">
                                                <component :is="getGoalTypeConfig(goal.type).icon" :size="16" :style="{ color: getGoalTypeConfig(goal.type).color }" />
                                                <span>{{ getGoalTypeConfig(goal.type).name }}</span>
                                            </div>
                                            <div class="goal-actions">
                                                <button @click="editGoal(goal)" class="action-btn edit">
                                                    <Edit :size="16" />
                                                </button>
                                                <div v-if="deleteConfirmId === goal.id" class="delete-confirm">
                                                    <span>Dzēst?</span>
                                                    <button @click="deleteGoal(goal.id)" :disabled="processingGoalId === goal.id" class="confirm-yes">Jā</button>
                                                    <button @click="deleteConfirmId = null" class="confirm-no">Nē</button>
                                                </div>
                                                <button v-else @click="deleteConfirmId = goal.id" :disabled="processingGoalId === goal.id" class="action-btn delete">
                                                    <Trash2 :size="16" />
                                                </button>
                                            </div>
                                        </div>
                                        <h4 class="goal-title">{{ goal.title }}</h4>
                                        <p v-if="goal.exercise" class="goal-exercise-name">
                                            <Dumbbell :size="12" />
                                            {{ goal.exercise.name }}
                                        </p>
                                        <p v-if="goal.description" class="goal-description">{{ goal.description }}</p>

                                        <!-- Progress Bar -->
                                        <div class="progress-section">
                                            <div class="progress-header">
                                                <span class="progress-label">Progress</span>
                                                <span class="progress-value">{{ getProgressPercentage(goal) }}%</span>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" :style="{ width: `${getProgressPercentage(goal)}%`, background: getGoalTypeConfig(goal.type).color }"></div>
                                            </div>
                                            <div class="progress-stats">
                                                <span>{{ goal.current_value }} / {{ goal.target_value }} {{ goal.unit }}</span>
                                                <span v-if="goal.deadline" class="deadline">
                                                    <Calendar :size="12" />
                                                    {{ new Date(goal.deadline).toLocaleDateString('lv-LV') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="goal-footer">
                                            <span v-if="goal.completed" class="auto-complete-badge">
                                                <CheckCircle :size="13" />
                                                Automātiski sasniegts!
                                            </span>
                                            <span v-else class="auto-track-badge">
                                                <Zap :size="12" />
                                                Auto-izsekots
                                            </span>
                                            <div v-if="goal.completed" class="achievement-badge">
                                                <Medal :size="14" />
                                                <span>Sasniegts!</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DeleteUser />
                    </div>

                    <!-- Right Column - Stats & Insights -->
                    <div class="right-column">
                        <!-- Stats Card -->
                        <div class="card-modern stats-card">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon stats-icon">
                                        <TrendingUp />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Mans progress</h2>
                                        <p class="card-subtitle">Tavs sasniegumu kopsavilkums</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <div class="stats-grid">
                                    <div class="stat-block">
                                        <div class="stat-value">{{ totalGoals }}</div>
                                        <div class="stat-label">Kopā mērķi</div>
                                    </div>
                                    <div class="stat-block">
                                        <div class="stat-value">{{ completedGoals }}</div>
                                        <div class="stat-label">Pabeigti</div>
                                    </div>
                                    <div class="stat-block">
                                        <div class="stat-value">{{ completionRate }}%</div>
                                        <div class="stat-label">Pabeigtība</div>
                                    </div>
                                </div>

                                <div class="overall-progress">
                                    <div class="progress-header">
                                        <span>Vispārējais progress</span>
                                        <span>{{ completionRate }}%</span>
                                    </div>
                                    <div class="progress-bar overall">
                                        <div class="progress-fill" :style="{ width: `${completionRate}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Goals Card -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon active-icon">
                                        <Flame />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Aktīvie mērķi</h2>
                                        <p class="card-subtitle">Mērķi pie kā strādā</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <div v-if="inProgressGoals.length === 0" class="no-active">
                                    <p>Nav aktīvu mērķu</p>
                                    <span>Izveido jaunu mērķi, lai sāktu progresu!</span>
                                </div>
                                <div v-else class="active-list">
                                    <div v-for="goal in inProgressGoals.slice(0, 4)" :key="goal.id" class="active-item">
                                        <div class="active-icon-wrapper" :style="{ background: getGoalTypeConfig(goal.type).bg }">
                                            <component :is="getGoalTypeConfig(goal.type).icon" :size="14" :style="{ color: getGoalTypeConfig(goal.type).color }" />
                                        </div>
                                        <div class="active-info">
                                            <div class="active-title">{{ goal.title }}</div>
                                            <div class="active-progress">{{ getProgressPercentage(goal) }}%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Achievements -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon recent-icon">
                                        <Crown />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Nesen pabeigtie</h2>
                                        <p class="card-subtitle">Tavi pēdējie sasniegumi</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <div v-if="recentCompleted.length === 0" class="no-recent">
                                    <div class="empty-small">
                                        <Medal :size="32" />
                                        <p>Vēl nav pabeigto mērķu</p>
                                        <span>Pabeidz savu pirmo mērķi!</span>
                                    </div>
                                </div>
                                <div v-else class="recent-list">
                                    <div v-for="goal in recentCompleted" :key="goal.id" class="recent-item">
                                        <div class="recent-icon" :style="{ background: getGoalTypeConfig(goal.type).bg }">
                                            <component :is="getGoalTypeConfig(goal.type).icon" :size="16" :style="{ color: getGoalTypeConfig(goal.type).color }" />
                                        </div>
                                        <div class="recent-info">
                                            <div class="recent-name">{{ goal.title }}</div>
                                            <div class="recent-date">
                                                {{ new Date(goal.updated_at).toLocaleDateString('lv-LV') }}
                                            </div>
                                        </div>
                                        <ChevronRight class="chevron" :size="16" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tips Card -->
                        <div class="card-modern tips-card">
                            <div class="card-header-modern">
                                <div class="header-left">
                                    <div class="header-icon tips-icon">
                                        <Sparkles />
                                    </div>
                                    <div>
                                        <h2 class="card-title">Padomi panākumiem</h2>
                                        <p class="card-subtitle">Kā sasniegt savus mērķus</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-divider"></div>
                            <div class="card-body">
                                <ul class="tips-list">
                                    <li class="tip-item">
                                        <div class="tip-dot"></div>
                                        <span>Nosaki konkrētus un izmērāmus mērķus</span>
                                    </li>
                                    <li class="tip-item">
                                        <div class="tip-dot"></div>
                                        <span>Sadalīti lielus mērķus mazākos posmos</span>
                                    </li>
                                    <li class="tip-item">
                                        <div class="tip-dot"></div>
                                        <span>Regulāri seko līdzi savam progresam</span>
                                    </li>
                                    <li class="tip-item">
                                        <div class="tip-dot"></div>
                                        <span>Atzīmē katru sasniegumu - tas motivē!</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    /* Base */
    .profile-page {
        min-height: 100vh;
        position: relative;
        background: #f5f7fb;
    }

    /* Animated Background */
    .animated-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 0;
    }

    .gradient-sphere {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.4;
    }

    .sphere-1 {
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(59,130,246,0.4) 0%, rgba(59,130,246,0) 70%);
        top: -200px;
        right: -100px;
        animation: float 20s ease-in-out infinite;
    }

    .sphere-2 {
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(249,115,22,0.3) 0%, rgba(249,115,22,0) 70%);
        bottom: -250px;
        left: -150px;
        animation: float 25s ease-in-out infinite reverse;
    }

    .sphere-3 {
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(139,92,246,0.3) 0%, rgba(139,92,246,0) 70%);
        top: 40%;
        left: 30%;
        animation: float 18s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% {
            transform: translate(0, 0) rotate(0deg);
        }

        33% {
            transform: translate(30px, -30px) rotate(120deg);
        }

        66% {
            transform: translate(-20px, 20px) rotate(240deg);
        }
    }

    .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
        position: relative;
        z-index: 1;
    }

    /* Hero Section */
    .hero-section {
        text-align: center;
        margin-bottom: 3rem;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(4px);
        padding: 0.5rem 1rem;
        border-radius: 100px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #f97316;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(249,115,22,0.2);
    }

    .badge-icon {
        width: 16px;
        height: 16px;
    }

    .hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 1rem;
        letter-spacing: -0.02em;
    }

        .hero-title .highlight {
            background: linear-gradient(135deg, #f97316, #ea580c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

    .hero-description {
        font-size: 1.125rem;
        color: #64748b;
        max-width: 600px;
        margin: 0 auto;
    }

    /* Grid Layout */
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    @media (max-width: 1024px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }

    .left-column {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .right-column {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Modern Card */
    .card-modern {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border-radius: 1.5rem;
        border: 1px solid rgba(226,232,240,0.6);
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
    }

        .card-modern:hover {
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.02);
            transform: translateY(-2px);
        }

    .card-header-modern {
        padding: 1.5rem 1.5rem 0 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-icon {
        width: 48px;
        height: 48px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff7ed;
        color: #f97316;
    }

    .profile-icon {
        background: #eff6ff;
        color: #3b82f6;
    }

    .password-icon {
        background: #fef2f2;
        color: #ef4444;
    }

    .goals-icon {
        background: #ecfdf5;
        color: #10b981;
    }

    .stats-icon {
        background: #fefce8;
        color: #eab308;
    }

    .active-icon {
        background: #fff7ed;
        color: #f97316;
    }

    .recent-icon {
        background: #f5f3ff;
        color: #8b5cf6;
    }

    .tips-icon {
        background: #fef3c7;
        color: #d97706;
    }

    .header-icon svg {
        width: 24px;
        height: 24px;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .card-subtitle {
        font-size: 0.875rem;
        color: #64748b;
        margin-top: 0.125rem;
    }

    .card-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        margin: 1rem 1.5rem;
    }

    .card-body {
        padding: 0 1.5rem 1.5rem 1.5rem;
    }

    /* Form Styles */
    .form-stack {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
    }

    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

        .input-group .input-icon {
            position: absolute;
            left: 1rem;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: white;
        }

            .input-group input:focus {
                outline: none;
                border-color: #f97316;
                box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
            }

            .input-group input.error {
                border-color: #ef4444;
            }

    .toggle-password {
        position: absolute;
        right: 1rem;
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .form-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 0.5rem;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #f97316, #ea580c);
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(249,115,22,0.3);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

    .btn-secondary {
        background: linear-gradient(135deg, #475569, #334155);
    }

        .btn-secondary:hover:not(:disabled) {
            box-shadow: 0 8px 20px rgba(71,85,105,0.3);
        }

    .btn-icon {
        width: 18px;
        height: 18px;
    }

    .success-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #ecfdf5;
        border-radius: 0.75rem;
        color: #10b981;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .success-icon {
        width: 16px;
        height: 16px;
    }

    /* Alerts */
    .alert-warning {
        display: flex;
        gap: 0.75rem;
        padding: 1rem;
        background: #fef3c7;
        border-radius: 0.75rem;
        border-left: 3px solid #f59e0b;
    }

    .alert-success {
        display: flex;
        gap: 0.75rem;
        padding: 1rem;
        background: #ecfdf5;
        border-radius: 0.75rem;
        border-left: 3px solid #10b981;
    }

    .alert-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .alert-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #92400e;
        margin-bottom: 0.25rem;
    }

    .alert-success .alert-title {
        color: #065f46;
    }

    .alert-text {
        font-size: 0.8125rem;
        color: #b45309;
    }

    .alert-success .alert-text {
        color: #047857;
    }

    .alert-link {
        background: none;
        border: none;
        color: #f97316;
        font-weight: 500;
        cursor: pointer;
        text-decoration: underline;
    }

    /* Add Goal Button */
    .btn-add {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-add:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-container {
        background: white;
        border-radius: 1.5rem;
        width: 90%;
        max-width: 500px;
        overflow: hidden;
        animation: modalFadeIn 0.2s ease;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* ── Goal Modal (gm-*) ───────────────────────────────── */
    .gm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9000;
        padding: 1rem;
    }

    .gm-panel {
        background: #fff;
        border-radius: 1.5rem;
        width: 100%;
        max-width: 560px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 24px 64px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
    }

    /* Header */
    .gm-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem 1.5rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .gm-header-icon {
        width: 46px;
        height: 46px;
        border-radius: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf5;
        color: #10b981;
        flex-shrink: 0;
    }

    .gm-type-workout   { background: #eff6ff; color: #3b82f6; }
    .gm-type-strength  { background: #fff7ed; color: #f97316; }
    .gm-type-endurance { background: #f5f3ff; color: #8b5cf6; }

    .gm-header-text { flex: 1; }

    .gm-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 0.125rem;
    }

    .gm-subtitle {
        font-size: 0.8125rem;
        color: #94a3b8;
        margin: 0;
    }

    .gm-close {
        width: 36px;
        height: 36px;
        border-radius: 0.625rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }

    .gm-close:hover {
        background: #fee2e2;
        border-color: #fca5a5;
        color: #dc2626;
    }

    /* Error */
    .gm-error {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        border-radius: 0;
        padding: 0.75rem 1.5rem;
        font-size: 0.8125rem;
        font-weight: 500;
    }

    /* Body */
    .gm-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.125rem;
        overflow-y: auto;
    }

    .gm-field {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }

    .gm-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .gm-required { color: #ef4444; }
    .gm-optional { color: #94a3b8; font-weight: 400; text-transform: none; letter-spacing: 0; }

    .gm-input {
        width: 100%;
        padding: 0.7rem 0.875rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        color: #0f172a;
        background: #fafafa;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        box-sizing: border-box;
    }

    .gm-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.12);
        background: #fff;
    }

    .gm-input::placeholder { color: #cbd5e1; }

    .gm-textarea {
        width: 100%;
        padding: 0.7rem 0.875rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        color: #0f172a;
        background: #fafafa;
        resize: vertical;
        min-height: 64px;
        font-family: inherit;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        box-sizing: border-box;
    }

    .gm-textarea:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.12);
        background: #fff;
    }

    .gm-textarea::placeholder { color: #cbd5e1; }

    /* Type pills */
    .gm-type-pills {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    .gm-pill {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 0.625rem 0.25rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 0.875rem;
        background: #fafafa;
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s;
    }

    .gm-pill:hover { background: #f1f5f9; }

    .gm-pill.active.pill-workout   { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
    .gm-pill.active.pill-strength  { border-color: #f97316; background: #fff7ed; color: #c2410c; }
    .gm-pill.active.pill-endurance { border-color: #8b5cf6; background: #f5f3ff; color: #6d28d9; }

    /* Two-col row */
    .gm-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    @media (max-width: 480px) {
        .gm-row { grid-template-columns: 1fr; }
        .gm-type-pills { grid-template-columns: repeat(2, 1fr); }
    }

    /* Footer */
    .gm-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
        padding: 1.125rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .gm-btn-cancel {
        padding: 0.625rem 1.25rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s;
    }

    .gm-btn-cancel:hover { background: #f1f5f9; }

    .gm-btn-save {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.5rem;
        background: linear-gradient(135deg, #f97316, #ea580c);
        border: none;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }

    .gm-btn-save:hover:not(:disabled) {
        box-shadow: 0 6px 16px rgba(249,115,22,0.35);
        transform: translateY(-1px);
    }

    .gm-btn-save:disabled { opacity: 0.65; cursor: not-allowed; }

    /* Modal animation */
    .modal-anim-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
    .modal-anim-leave-active { transition: all 0.2s ease; }
    .modal-anim-enter-from  { opacity: 0; }
    .modal-anim-leave-to    { opacity: 0; }
    .modal-anim-enter-from .gm-panel  { transform: scale(0.95) translateY(12px); }
    .modal-anim-leave-to .gm-panel    { transform: scale(0.97) translateY(4px); }

    /* ── Password strength indicator ────────────────────── */
    .pw-rules {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0.625rem 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.625rem;
        margin-top: 0.125rem;
    }

    .pw-rule {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.78rem;
        color: #94a3b8;
        transition: color 0.2s;
    }

    .pw-rule.met { color: #10b981; }

    .pw-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #cbd5e1;
        flex-shrink: 0;
        transition: background 0.2s;
    }

    .pw-rule.met .pw-dot { background: #10b981; }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .select-custom {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.95rem;
        background: white;
        cursor: pointer;
    }

    /* Auto-track info banner in modal */
    .gm-auto-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 0.625rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.78rem;
        color: #1d4ed8;
    }

    .gm-select {
        appearance: none;
        cursor: pointer;
    }

    /* Goal card extras */
    .goal-exercise-name {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.75rem;
        color: #64748b;
        margin: 0 0 0.25rem;
    }

    .auto-track-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.72rem;
        font-weight: 500;
        color: #6366f1;
        background: #eef2ff;
        padding: 0.2rem 0.5rem;
        border-radius: 100px;
    }

    .auto-complete-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #059669;
        background: #ecfdf5;
        padding: 0.2rem 0.5rem;
        border-radius: 100px;
    }

    /* Goals List */
    .goals-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .goal-card {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1rem;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }

        .goal-card:hover {
            border-color: #cbd5e1;
        }

    .goal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .goal-type {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.75rem;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .goal-actions {
        display: flex;
        gap: 0.5rem;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .action-btn {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        opacity: 1 !important;
        visibility: visible !important;
    }

        .action-btn.edit {
            color: #3b82f6;
        }

        .action-btn.edit:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .action-btn.delete {
            color: #ef4444;
        }

        .action-btn.delete:hover {
            background: #fef2f2;
            border-color: #ef4444;
            color: #ef4444;
        }

    .goal-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 0.25rem 0;
    }

    .goal-description {
        font-size: 0.8125rem;
        color: #64748b;
        margin-bottom: 0.75rem;
    }

    .progress-section {
        margin: 0.75rem 0;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }

    .progress-label {
        color: #64748b;
    }

    .progress-value {
        font-weight: 600;
        color: #1e293b;
    }

    .progress-bar {
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s ease;
    }

    .progress-stats {
        display: flex;
        justify-content: space-between;
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.5rem;
    }

    .deadline {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .goal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .complete-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: none;
        border: none;
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

        .complete-btn:hover {
            background: #f1f5f9;
        }

        .complete-btn.completed {
            color: #10b981;
        }

    .circle-outline {
        width: 14px;
        height: 14px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        display: inline-block;
    }

    .achievement-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        padding: 0.25rem 0.5rem;
        border-radius: 100px;
        font-size: 0.7rem;
        font-weight: 600;
        color: white;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-block {
        text-align: center;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 1rem;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #f97316;
        line-height: 1.2;
    }

    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }

    .overall-progress {
        margin-top: 0.5rem;
    }

        .overall-progress .progress-header {
            margin-bottom: 0.5rem;
        }

        .overall-progress .progress-bar {
            height: 8px;
        }

        .overall-progress .progress-fill {
            background: linear-gradient(90deg, #f97316, #ea580c);
        }

    /* Active Goals List */
    .active-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .active-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .active-icon-wrapper {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .active-info {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .active-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #1e293b;
    }

    .active-progress {
        font-size: 0.75rem;
        font-weight: 600;
        color: #f97316;
    }

    .no-active {
        text-align: center;
        padding: 1rem;
        color: #94a3b8;
        font-size: 0.875rem;
    }

    /* Recent List */
    .recent-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .recent-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        border-radius: 0.75rem;
        transition: all 0.2s;
        cursor: pointer;
    }

        .recent-item:hover {
            background: #f8fafc;
        }

    .recent-icon {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .recent-info {
        flex: 1;
    }

    .recent-name {
        font-size: 0.875rem;
        font-weight: 500;
        color: #1e293b;
    }

    .recent-date {
        font-size: 0.7rem;
        color: #94a3b8;
    }

    .chevron {
        color: #cbd5e1;
    }

    .no-recent {
        text-align: center;
        padding: 1.5rem;
    }

    .empty-small {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: #94a3b8;
    }

        .empty-small p {
            font-size: 0.875rem;
            margin: 0;
        }

        .empty-small span {
            font-size: 0.75rem;
        }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
    }

    .empty-icon-wrapper {
        width: 64px;
        height: 64px;
        background: #f1f5f9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .empty-icon {
        width: 32px;
        height: 32px;
        color: #94a3b8;
    }

    .empty-state h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .empty-state p {
        font-size: 0.875rem;
        color: #64748b;
        margin-bottom: 1rem;
    }

    /* Loading State */
    .loading-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        gap: 1rem;
    }

    .loading-ring {
        width: 40px;
        height: 40px;
        border: 3px solid #e2e8f0;
        border-top-color: #f97316;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .loading-state p {
        color: #64748b;
        font-size: 0.875rem;
    }

    /* Error Message */
    .error-message {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fef2f2;
        color: #dc2626;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    /* Tips Card */
    .tips-card {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
    }

    .tips-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .tip-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        font-size: 0.875rem;
        color: #92400e;
    }

    .tip-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #f97316;
        margin-top: 0.5rem;
    }

    /* Notification Toast */
    .notification-toast {
        position: fixed;
        top: 1.5rem;
        right: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0.875rem;
        font-size: 0.875rem;
        font-weight: 500;
        z-index: 9999;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .notification-toast.success {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .notification-toast.error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .toast-enter-active, .toast-leave-active {
        transition: all 0.3s ease;
    }

    .toast-enter-from, .toast-leave-to {
        opacity: 0;
        transform: translateX(1rem);
    }

    /* Password hints */
    .password-hint {
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    .password-hint.mismatch {
        color: #ef4444;
    }

    .password-hint.match {
        color: #10b981;
    }

    /* Modal error */
    .modal-error {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #fef2f2;
        color: #dc2626;
        padding: 0.625rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8125rem;
        margin-bottom: 1rem;
        border: 1px solid #fecaca;
    }

    /* Modal form helpers */
    .required {
        color: #ef4444;
    }

    .optional {
        color: #94a3b8;
        font-weight: 400;
    }

    /* Textarea */
    .textarea-custom {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.9rem;
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
        background: white;
        transition: border-color 0.2s;
        color: #1e293b;
    }

    .textarea-custom:focus {
        outline: none;
        border-color: #f97316;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
    }

    /* Inline delete confirm */
    .delete-confirm {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 0.5rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        color: #dc2626;
        font-weight: 500;
    }

    .confirm-yes {
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 0.375rem;
        padding: 0.125rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .confirm-yes:hover {
        background: #dc2626;
    }

    .confirm-no {
        background: white;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 0.125rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .confirm-no:hover {
        background: #f8fafc;
    }

    /* Animations */
    .fade-enter-active, .fade-leave-active {
        transition: all 0.3s ease;
    }

    .fade-enter-from, .fade-leave-to {
        opacity: 0;
        transform: translateY(-10px);
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }

        .hero-title {
            font-size: 1.75rem;
        }

        .hero-description {
            font-size: 1rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .card-header-modern {
            flex-direction: column;
            gap: 1rem;
        }

        .btn-add {
            align-self: flex-start;
        }
    }
</style>
