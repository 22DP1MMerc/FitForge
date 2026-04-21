<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { ref, computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordValidation = computed(() => {
    const p = form.password;
    return {
        minLength: p.length >= 8,
        hasUppercase: /[A-Z]/.test(p),
        hasSpecialOrNumber: /[0-9!@#$%^&*(),.?":{}|<>]/.test(p),
    };
});

const isPasswordValid = computed(() =>
    passwordValidation.value.minLength &&
    passwordValidation.value.hasUppercase &&
    passwordValidation.value.hasSpecialOrNumber
);

// e-pasta regex — vienkāršs bet pietiekams
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const submit = () => {
    // tīram vecās kļūdas
    form.clearErrors();

    // pārbaudam e-pastu paši, nevis ļaujam pārlūkam
    if (!emailRegex.test(form.email)) {
        form.setError('email', 'Ievadi derīgu e-pasta adresi (piem. epasts@piemers.lv).');
        return;
    }

    if (!isPasswordValid.value) {
        form.setError('password', 'Parolei jābūt vismaz 8 rakstzīmēm, jāsatur lielais burts un simbols vai cipars.');
        return;
    }

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onSuccess: () => console.log('Reģistrācija veiksmīga, pārbaudi e-pastu'),
        onError: (errors) => console.error('Reģistrācija neizdevās:', errors),
    });
};

const togglePasswordVisibility = () => { showPassword.value = !showPassword.value; };
const toggleConfirmPasswordVisibility = () => { showConfirmPassword.value = !showConfirmPassword.value; };
</script>

<template>
    <AppLayout>
        <AuthBase title="" description="">
            <Head title="Reģistrācija" />

            <div class="page">
                <!-- kreisā dekoratīvā puse — tikai desktop -->
                <div class="panel-left" aria-hidden="true">
                    <div class="panel-inner">
                        <div class="brand-mark">⚡</div>
                        <p class="panel-tagline">Sāc savu ceļu šodien.</p>
                        <ul class="panel-perks">
                            <li>✓ Izseko savus treniņus</li>
                            <li>✓ Seko progresam</li>
                            <li>✓ Sasniedz mērķus</li>
                        </ul>
                    </div>
                </div>

                <!-- labā gaišā puse — forma -->
                <div class="panel-right">
                    <div class="form-inner">
                        <div class="form-header">
                            <h1 class="form-title">Izveidot kontu</h1>
                            <p class="form-subtitle">Aizpildi formu, lai sāktu</p>
                        </div>

                        <!-- novalidate — mūsu pašu validācija latviski -->
                        <form @submit.prevent="submit" novalidate class="auth-form">

                            <!-- vārds un e-pasts blakus uz plašiem ekrāniem -->
                            <div class="field-row">
                                <div class="field-group">
                                    <label for="name" class="field-label">Vārds</label>
                                    <input id="name"
                                           type="text"
                                           required
                                           autofocus
                                           :tabindex="1"
                                           autocomplete="name"
                                           v-model="form.name"
                                           placeholder="Pilns vārds"
                                           class="field-input" />
                                    <div v-if="form.errors.name" class="field-error">
                                        <svg class="error-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" /><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="field-group">
                                    <label for="email" class="field-label">E-pasta adrese</label>
                                    <input id="email"
                                           type="email"
                                           required
                                           :tabindex="2"
                                           autocomplete="email"
                                           v-model="form.email"
                                           placeholder="epasts@piemers.lv"
                                           class="field-input" />
                                    <div v-if="form.errors.email" class="field-error">
                                        <svg class="error-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" /><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                        {{ form.errors.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="field-group">
                                <label for="password" class="field-label">Parole</label>
                                <div class="input-wrap">
                                    <input id="password"
                                           :type="showPassword ? 'text' : 'password'"
                                           required
                                           :tabindex="3"
                                           autocomplete="new-password"
                                           v-model="form.password"
                                           placeholder="••••••••"
                                           class="field-input" />
                                    <button type="button"
                                            @click="togglePasswordVisibility"
                                            class="eye-btn"
                                            :tabindex="4"
                                            aria-label="Rādīt/slēpt paroli">
                                        <Eye v-if="!showPassword" class="eye-icon" />
                                        <EyeOff v-else class="eye-icon" />
                                    </button>
                                </div>

                                <!-- paroles prasību indikators — parādās tikai rakstot -->
                                <div class="pw-rules" v-if="form.password.length > 0">
                                    <div class="pw-rule" :class="{ met: passwordValidation.minLength }">
                                        <span class="pw-dot"></span> Vismaz 8 rakstzīmes
                                    </div>
                                    <div class="pw-rule" :class="{ met: passwordValidation.hasUppercase }">
                                        <span class="pw-dot"></span> Lielais burts (A–Z)
                                    </div>
                                    <div class="pw-rule" :class="{ met: passwordValidation.hasSpecialOrNumber }">
                                        <span class="pw-dot"></span> Cipars vai simbols
                                    </div>
                                </div>

                                <div v-if="form.errors.password" class="field-error">
                                    <svg class="error-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" /><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <div class="field-group">
                                <label for="password_confirmation" class="field-label">Apstiprināt paroli</label>
                                <div class="input-wrap">
                                    <input id="password_confirmation"
                                           :type="showConfirmPassword ? 'text' : 'password'"
                                           required
                                           :tabindex="5"
                                           autocomplete="new-password"
                                           v-model="form.password_confirmation"
                                           placeholder="••••••••"
                                           class="field-input" />
                                    <button type="button"
                                            @click="toggleConfirmPasswordVisibility"
                                            class="eye-btn"
                                            :tabindex="6"
                                            aria-label="Rādīt/slēpt apstiprinājuma paroli">
                                        <Eye v-if="!showConfirmPassword" class="eye-icon" />
                                        <EyeOff v-else class="eye-icon" />
                                    </button>
                                </div>
                                <div v-if="form.errors.password_confirmation" class="field-error">
                                    <svg class="error-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" /><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                    {{ form.errors.password_confirmation }}
                                </div>
                            </div>

                            <button type="submit" class="submit-btn" tabindex="7" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="spin-icon" />
                                <span>{{ form.processing ? 'Izveido kontu...' : 'Izveidot kontu' }}</span>
                            </button>

                            <p class="switch-link">
                                Jau ir konts?
                                <a :href="route('login')" :tabindex="8" class="switch-a">Pieteikties</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </AuthBase>
    </AppLayout>
</template>

<style scoped>
    @import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap');

    .page {
        display: flex;
        min-height: calc(100vh - 64px); /* vieta zem navbar */
        background: #f5f5f5;
    }

    /* --- kreisā oranžā puse --- */
    .panel-left {
        display: none;
        position: relative;
        width: 40%;
        flex-shrink: 0;
        background: linear-gradient(150deg, #ff6b00 0%, #d04e00 50%, #1a1a1a 100%);
        overflow: hidden;
    }

    @media (min-width: 900px) {
        .panel-left {
            display: flex;
        }
    }

    .panel-left::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
    }

    .panel-left::after {
        content: '';
        position: absolute;
        bottom: -80px;
        left: -80px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255,255,255,0.04);
    }

    .panel-inner {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 4rem;
        gap: 2rem;
    }

    .brand-mark {
        font-size: 3.5rem;
        line-height: 1;
    }

    .panel-tagline {
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 3rem;
        font-weight: 800;
        color: #fff;
        line-height: 1.05;
        margin: 0;
        text-transform: uppercase;
    }

    .panel-perks {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

        .panel-perks li {
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            color: rgba(255,255,255,0.75);
        }

    /* --- labā gaišā puse --- */
    .panel-right {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.5rem;
        background: #ffffff;
    }

    @media (min-width: 480px) {
        .panel-right {
            padding: 3rem 2.5rem;
        }
    }

    .form-inner {
        width: 100%;
        max-width: 560px;
    }

    .form-header {
        margin-bottom: 2rem;
    }

    .form-title {
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 2.6rem;
        font-weight: 800;
        color: #111;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 0.3rem;
        line-height: 1;
    }

    .form-subtitle {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        color: #888;
        margin: 0;
    }

    .auth-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    /* vārds + e-pasts blakus uz plašiem ekrāniem */
    .field-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    @media (min-width: 500px) {
        .field-row {
            grid-template-columns: 1fr 1fr;
        }
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .field-label {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.9px;
    }

    .field-input {
        width: 100%;
        padding: 0.85rem 1.1rem;
        background: #fafafa;
        border: 1.5px solid #e5e5e5;
        border-radius: 9px;
        color: #111;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
        outline: none;
    }

        .field-input::placeholder {
            color: #bbb;
        }

        .field-input:focus {
            border-color: #ff6b00;
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
            background: #fff;
        }

    .input-wrap {
        position: relative;
    }

        .input-wrap .field-input {
            padding-right: 3.2rem;
        }

    .eye-btn {
        position: absolute;
        right: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        cursor: pointer;
        color: #bbb;
        padding: 0.2rem;
        transition: color 0.2s;
        display: flex;
        align-items: center;
    }

        .eye-btn:hover {
            color: #ff6b00;
        }

    .eye-icon {
        width: 18px;
        height: 18px;
    }

    /* kļūdu ziņojumi */
    .field-error {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        color: #dc2626;
        background: rgba(220, 38, 38, 0.05);
        border: 1px solid rgba(220, 38, 38, 0.15);
        border-radius: 6px;
        padding: 0.45rem 0.65rem;
    }

    .error-icon {
        width: 11px;
        height: 11px;
        flex-shrink: 0;
    }

    /* paroles prasību indikators */
    .pw-rules {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.6rem 0.75rem;
        background: #f9f9f9;
        border: 1px solid #ebebeb;
        border-radius: 7px;
    }

    .pw-rule {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.78rem;
        color: #aaa;
        transition: color 0.25s;
    }

        .pw-rule.met {
            color: #16a34a;
        }

    .pw-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ddd;
        flex-shrink: 0;
        transition: background 0.25s;
    }

    .pw-rule.met .pw-dot {
        background: #16a34a;
    }

    .submit-btn {
        margin-top: 0.25rem;
        width: 100%;
        padding: 1rem;
        background: #ff6b00;
        color: #fff;
        border: none;
        border-radius: 9px;
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

        .submit-btn:hover:not(:disabled) {
            background: #e65c00;
            transform: translateY(-1px);
        }

        .submit-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #e5e5e5;
            color: #aaa;
            cursor: not-allowed;
        }

    .spin-icon {
        width: 18px;
        height: 18px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .switch-link {
        text-align: center;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.875rem;
        color: #999;
        margin: 0;
    }

    .switch-a {
        color: #ff6b00;
        text-decoration: none;
        font-weight: 600;
        margin-left: 0.25rem;
    }

        .switch-a:hover {
            color: #e65c00;
            text-decoration: underline;
        }
</style>
