<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, CheckCircle, LogOut } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="Verificēt e-pastu" description="Lūdzu, verificējiet savu e-pasta adresi, noklikšķinot uz saites, ko nosūtījām uz jūsu e-pastu.">
        <Head title="E-pasta verifikācija" />

        <div class="verification-container">
            <!-- Success Message -->
            <div v-if="status === 'verification-link-sent'" class="success-message">
                <CheckCircle class="success-icon" />
                <div class="success-content">
                    <p class="success-title">Jauna verifikācijas saite ir nosūtīta!</p>
                    <p class="success-text">Lūdzu, pārbaudiet savu e-pasta iesūtni un mapi "Spam".</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="verification-card">
                <!-- Icon -->
                <div class="icon-wrapper">
                    <div class="icon-circle">
                        <Mail class="icon-mail" />
                    </div>
                </div>

                <!-- Title - Centered -->
                <h2 class="card-title">Pārbaudiet savu e-pastu</h2>

                <!-- Description - Centered -->
                <p class="card-description">
                    Mēs nosūtījām verifikācijas saiti uz jūsu e-pasta adresi.
                    <br>
                    Lūdzu, noklikšķiniet uz saites e-pastā, lai aktivizētu savu kontu.
                </p>

                <!-- Tips -->
                <div class="tips-box">
                    <p class="tips-title">📧 Nesaņēmāt e-pastu?</p>
                    <ul class="tips-list">
                        <li>Pārbaudiet mapi "Spam" vai "Junk"</li>
                        <li>Pārliecinieties, ka ievadījāt pareizu e-pasta adresi</li>
                        <li>Pievienojiet mūs saviem kontaktiem, lai nākotnē nesaņemtu e-pastus spama mapē</li>
                    </ul>
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <button @click="submit"
                            :disabled="form.processing"
                            class="btn-primary">
                        <LoaderCircle v-if="form.processing" class="btn-spinner" />
                        <Mail v-else class="btn-icon" />
                        {{ form.processing ? 'Nosūta...' : 'Nosūtīt verifikācijas e-pastu vēlreiz' }}
                    </button>

                    <TextLink :href="route('logout')"
                              method="post"
                              as="button"
                              class="btn-logout">
                        <LogOut class="btn-icon-small" />
                        Izrakstīties
                    </TextLink>
                </div>
            </div>

            <!-- Help Text -->
            <p class="help-text">
                Ja jums joprojām ir problēmas, lūdzu, sazinieties ar atbalsta dienestu
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
    /* Container */
    .verification-container {
        max-width: 28rem;
        margin: 0 auto;
    }

    /* Success Message */
    .success-message {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 0.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        animation: fadeIn 0.3s ease-out;
    }

    .success-icon {
        width: 1.25rem;
        height: 1.25rem;
        color: #16a34a;
        flex-shrink: 0;
        margin-top: 0.125rem;
    }

    .success-content {
        flex: 1;
    }

    .success-title {
        font-weight: 500;
        color: #166534;
        margin-bottom: 0.25rem;
    }

    .success-text {
        font-size: 0.875rem;
        color: #15803d;
    }

    /* Verification Card */
    .verification-card {
        background-color: #ffffff;
        border-radius: 1rem;
        border: 1px solid #f3f4f6;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        padding: 2rem;
    }

    /* Icon */
    .icon-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 1.5rem;
    }

    .icon-circle {
        width: 4rem;
        height: 4rem;
        background-color: #fff7ed;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-mail {
        width: 2rem;
        height: 2rem;
        color: #ea580c;
    }

    /* Card Text - All Centered */
    .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        text-align: center;
        color: #111827;
        margin-bottom: 0.75rem;
    }

    .card-description {
        text-align: center;
        color: #4b5563;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    /* Tips Box - Left aligned (keeps readability) */
    .tips-box {
        background-color: #f9fafb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .tips-title {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        text-align: left;
    }

    .tips-list {
        font-size: 0.875rem;
        color: #4b5563;
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

        .tips-list li {
            margin-bottom: 0.25rem;
            padding-left: 1rem;
            position: relative;
            text-align: left;
        }

            .tips-list li::before {
                content: "•";
                position: absolute;
                left: 0;
                color: #ea580c;
            }

    /* Button Group */
    .button-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Primary Button */
    .btn-primary {
        width: 100%;
        background-color: #ea580c;
        color: #ffffff;
        font-weight: 500;
        padding: 0.625rem 1rem;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

        .btn-primary:hover:not(:disabled) {
            background-color: #c2410c;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

    .btn-spinner {
        width: 1rem;
        height: 1rem;
        animation: spin 1s linear infinite;
    }

    .btn-icon {
        width: 1rem;
        height: 1rem;
    }

    /* Logout Button */
    .btn-logout {
        width: 100%;
        background-color: transparent;
        color: #4b5563;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

        .btn-logout:hover {
            color: #111827;
            background-color: #f9fafb;
        }

    .btn-icon-small {
        width: 1rem;
        height: 1rem;
    }

    /* Help Text */
    .help-text {
        text-align: center;
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 1.5rem;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
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

    /* Responsive Design */
    @media (max-width: 640px) {
        .verification-card {
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
        }

        .card-description {
            font-size: 0.875rem;
        }

        .tips-title {
            font-size: 0.8125rem;
        }

        .tips-list li {
            font-size: 0.8125rem;
        }
    }

    
</style>
