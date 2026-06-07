<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CheckCircle, Mail, User, ShieldAlert, Sparkles, Loader2, Eye, EyeOff, Lock } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

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

</script>

<template>
    <AppLayout>
        <Head title="Profila iestatījumi" />

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
                        Pārvaldi savu kontu un uzlabo konta drošību.
                    </p>
                </div>

                <div class="content-grid">
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

                        <DeleteUser />
                    </div>
                    <div class="right-column">
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

        .card-header-modern {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>
