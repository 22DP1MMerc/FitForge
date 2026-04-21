<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, CheckCircle, LogOut } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

// nosūta verifikācijas e-pastu vēlreiz
const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <Head title="E-pasta verifikācija" />

    <div class="page">

        <!-- veiksmīgas nosūtīšanas paziņojums -->
        <div v-if="status === 'verification-link-sent'" class="success-bar">
            <CheckCircle class="success-icon" />
            <div>
                <p class="success-title">Saite nosūtīta!</p>
                <p class="success-sub">Pārbaudiet iesūtni un mapi "Spam".</p>
            </div>
        </div>

        <!-- galvenā karte -->
        <div class="verify-card">

            <!-- e-pasta ikona -->
            <div class="icon-circle">
                <Mail class="icon-mail" />
            </div>

            <h2 class="card-title">Pārbaudiet savu e-pastu</h2>

            <p class="card-desc">
                Mēs nosūtījām verifikācijas saiti uz jūsu e-pasta adresi.<br />
                Noklikšķiniet uz saites, lai aktivizētu kontu.
            </p>

            <!-- padomi ja e-pasts netika saņemts -->
            <div class="tips-box">
                <p class="tips-title">📧 Nesaņēmāt e-pastu?</p>
                <ul class="tips-list">
                    <li>Pārbaudiet mapi "Spam" vai "Junk"</li>
                    <li>Pārliecinieties, ka ievadījāt pareizu adresi</li>
                    <li>Pievienojiet mūs kontaktiem, lai izvairītos no spama</li>
                </ul>
            </div>

            <div class="btn-group">
                <!-- nosūtīt vēlreiz -->
                <button @click="submit"
                        :disabled="form.processing"
                        class="btn-primary">
                    <LoaderCircle v-if="form.processing" class="spin-icon" />
                    <Mail v-else class="btn-icon" />
                    {{ form.processing ? 'Nosūta...' : 'Nosūtīt vēlreiz' }}
                </button>

                <!-- izrakstīties — natīvā forma ar Laravel CSRF tokenu no meta taga -->
                <!-- vienkārša saite — GET pieprasījums, nav CSRF problēmu -->
                <a :href="route('logout.get')" class="btn-logout">
                    <LogOut class="btn-icon" />
                    Izrakstīties
                </a>
            </div>
        </div>

        <p class="help-text">Problēmu gadījumā sazinieties ar atbalsta dienestu.</p>
    </div>
</template>

<style scoped>
    @import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap');

    .page {
        min-height: 100vh;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
    }

    .success-bar {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        width: 100%;
        max-width: 480px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 9px;
        padding: 0.85rem 1rem;
        margin-bottom: 1.25rem;
        animation: fadeDown 0.3s ease;
    }

    .success-icon {
        width: 18px;
        height: 18px;
        color: #16a34a;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .success-title {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 600;
        color: #166534;
        margin: 0 0 0.15rem;
    }

    .success-sub {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        color: #15803d;
        margin: 0;
    }

    .verify-card {
        width: 100%;
        max-width: 480px;
        background: #ffffff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 2.5rem 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.25rem;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }

    .icon-circle {
        width: 64px;
        height: 64px;
        background: #fff7ed;
        border: 1px solid rgba(255, 107, 0, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-mail {
        width: 28px;
        height: 28px;
        color: #ff6b00;
    }

    .card-title {
        font-family: 'Barlow Condensed', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: #111;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
        text-align: center;
    }

    .card-desc {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        color: #777;
        text-align: center;
        line-height: 1.6;
        margin: 0;
    }

    .tips-box {
        width: 100%;
        background: #f9f9f9;
        border: 1px solid #ebebeb;
        border-radius: 9px;
        padding: 1rem 1.1rem;
    }

    .tips-title {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        color: #555;
        margin: 0 0 0.6rem;
    }

    .tips-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

        .tips-list li {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.8rem;
            color: #888;
            padding-left: 1rem;
            position: relative;
        }

            .tips-list li::before {
                content: '•';
                position: absolute;
                left: 0;
                color: #ff6b00;
            }

    .btn-group {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .btn-primary {
        width: 100%;
        padding: 0.95rem 1rem;
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

        .btn-primary:hover:not(:disabled) {
            background: #e65c00;
            transform: translateY(-1px);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            background: #e5e5e5;
            color: #aaa;
            cursor: not-allowed;
        }

    /* logout forma bez vizuālā stila */
    .logout-form {
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .btn-logout {
        width: 100%;
        padding: 0.75rem 1rem;
        background: transparent;
        color: #888;
        border: 1.5px solid #e5e5e5;
        border-radius: 9px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: color 0.2s, border-color 0.2s, background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-sizing: border-box;
    }

        .btn-logout:hover {
            color: #333;
            border-color: #ccc;
            background: #f5f5f5;
        }

    .btn-icon {
        width: 16px;
        height: 16px;
    }

    .spin-icon {
        width: 16px;
        height: 16px;
        animation: spin 1s linear infinite;
    }

    .help-text {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.75rem;
        color: #bbb;
        text-align: center;
        margin-top: 1.25rem;
    }

    @keyframes fadeDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 480px) {
        .verify-card {
            padding: 1.75rem 1.25rem;
        }

        .card-title {
            font-size: 1.6rem;
        }
    }
</style>
