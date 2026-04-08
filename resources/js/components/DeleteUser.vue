<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Trash2, AlertTriangle, ShieldAlert, X } from 'lucide-vue-next';

const confirmingUserDeletion = ref(false);
const password = ref('');

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingUserDeletion.value = false;
            password.value = '';
        },
        onFinish: () => {
            form.reset('password');
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    password.value = '';
    form.reset('password');
};
</script>

<template>
    <div class="delete-account-wrapper">
        <!-- Delete Account Card -->
        <div class="card-modern delete-card">
            <div class="card-header-modern">
                <div class="header-left">
                    <div class="header-icon delete-icon">
                        <Trash2 />
                    </div>
                    <div>
                        <h2 class="card-title">Dzēst kontu</h2>
                        <p class="card-subtitle">Neatgriezeniski dzēst savu kontu un visus datus</p>
                    </div>
                </div>
            </div>
            <div class="card-divider"></div>
            <div class="card-body">
                <!-- Warning Box -->
                <div class="delete-warning">
                    <div class="warning-icon">
                        <AlertTriangle />
                    </div>
                    <div class="warning-content">
                        <h4 class="warning-title">⚠️ Brīdinājums</h4>
                        <p class="warning-text">
                            Lūdzu, rīkojieties uzmanīgi - šo darbību nevar atsaukt. Visi jūsu dati tiks neatgriezeniski dzēsti.
                        </p>
                    </div>
                </div>

                <!-- Delete Button -->
                <button @click="confirmUserDeletion" class="btn-danger">
                    <Trash2 size="18" />
                    Dzēst kontu
                </button>
            </div>
        </div>

        <!-- Custom Modal (not using the broken Dialog component) -->
        <div v-if="confirmingUserDeletion" class="modal-overlay" @click.self="closeModal">
            <div class="modal-container delete-modal">
                <div class="modal-header">
                    <div class="modal-header-icon">
                        <ShieldAlert />
                    </div>
                    <h3>Vai tiešām vēlaties dzēst savu kontu?</h3>
                    <button @click="closeModal" class="modal-close">
                        <X size="20" />
                    </button>
                </div>

                <div class="modal-body">
                    <p class="modal-warning-text">
                        Šī darbība ir neatgriezeniska. Visi tavi dati, tostarp:
                    </p>
                    <ul class="data-list">
                        <li>✓ Profila informācija</li>
                        <li>✓ Visi mērķi un progress</li>
                        <li>✓ Sasniegumi un statistika</li>
                        <li>✓ Personīgie iestatījumi</li>
                    </ul>
                    <p class="modal-warning-text bold">
                        tiks dzēsti uz visiem laikiem!
                    </p>

                    <div class="password-confirm">
                        <Label class="form-label">Ievadi paroli, lai apstiprinātu</Label>
                        <Input type="password"
                               v-model="form.password"
                               placeholder="Tava parole"
                               @keyup.enter="deleteUser"
                               :class="{ 'error': form.errors.password }" />
                        <InputError :message="form.errors.password" />
                    </div>
                </div>

                <div class="modal-footer delete-footer">
                    <button @click="closeModal" class="btn-outline">
                        Atcelt
                    </button>
                    <button @click="deleteUser" :disabled="form.processing" class="btn-danger-modal">
                        <div v-if="form.processing" class="loading-spinner-small"></div>
                        <Trash2 v-else size="16" />
                        {{ form.processing ? 'Dzēš...' : 'Dzēst kontu' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .delete-account-wrapper {
        margin-top: 0.5rem;
    }

    /* Card styling matching your modern design */
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
        }

    .delete-card {
        border-color: rgba(239,68,68,0.2);
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
    }

    .delete-icon {
        background: #fef2f2;
        color: #ef4444;
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

    /* Warning Box */
    .delete-warning {
        display: flex;
        gap: 1rem;
        background: #fef2f2;
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-left: 3px solid #ef4444;
    }

    .warning-icon {
        width: 40px;
        height: 40px;
        background: #fee2e2;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #dc2626;
    }

        .warning-icon svg {
            width: 20px;
            height: 20px;
        }

    .warning-content {
        flex: 1;
    }

    .warning-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #991b1b;
        margin: 0 0 0.25rem 0;
    }

    .warning-text {
        font-size: 0.8125rem;
        color: #7f1d1d;
        margin: 0;
        line-height: 1.4;
    }

    /* Danger Button */
    .btn-danger {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        justify-content: center;
    }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(239,68,68,0.3);
        }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
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
        max-width: 450px;
        overflow: hidden;
        animation: modalFadeIn 0.2s ease;
    }

    .delete-modal {
        border-top: 4px solid #ef4444;
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

    .modal-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem 1.5rem 0.5rem 1.5rem;
        position: relative;
    }

    .modal-header-icon {
        width: 48px;
        height: 48px;
        background: #fef2f2;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ef4444;
    }

        .modal-header-icon svg {
            width: 24px;
            height: 24px;
        }

    .modal-header h3 {
        flex: 1;
        font-size: 1.125rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        padding: 0.25rem;
        display: flex;
        align-items: center;
    }

    .modal-body {
        padding: 1rem 1.5rem;
    }

    .modal-warning-text {
        font-size: 0.875rem;
        color: #475569;
        margin-bottom: 0.75rem;
    }

        .modal-warning-text.bold {
            font-weight: 600;
            color: #dc2626;
            margin-top: 0.75rem;
        }

    .data-list {
        list-style: none;
        padding: 0;
        margin: 0.75rem 0;
    }

        .data-list li {
            font-size: 0.8125rem;
            color: #64748b;
            padding: 0.25rem 0;
        }

    .password-confirm {
        margin-top: 1.25rem;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
        display: block;
        margin-bottom: 0.5rem;
    }

    .password-confirm input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

        .password-confirm input:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
        }

        .password-confirm input.error {
            border-color: #ef4444;
        }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 1rem 1.5rem 1.5rem 1.5rem;
    }

    .delete-footer {
        justify-content: space-between;
    }

    .btn-outline {
        padding: 0.625rem 1.25rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

    .btn-danger-modal {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        background: #ef4444;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }

        .btn-danger-modal:hover:not(:disabled) {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-danger-modal:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

    .loading-spinner-small {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Responsive */
    @media (max-width: 640px) {
        .modal-header {
            flex-wrap: wrap;
        }

            .modal-header h3 {
                width: 100%;
                order: 1;
            }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    }
</style>
