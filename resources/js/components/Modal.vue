<template>
    <Teleport to="body">
        <div v-if="isVisible" class="modal-overlay" @click.self="closeModal">
            <div class="modal-container" :class="modalTypeClass">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg v-if="modalType === 'confirm'" class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-if="modalType === 'alert'" class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-if="modalType === 'success'" class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-if="modalType === 'error'" class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="modal-title">{{ title }}</h3>
                </div>

                <div class="modal-body">
                    <p class="modal-message">{{ message }}</p>
                    <div v-if="details" class="modal-details">
                        <div v-for="(value, key) in details" :key="key" class="detail-item">
                            <span class="detail-label">{{ key }}:</span>
                            <span class="detail-value">{{ value }}</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button v-if="modalType === 'confirm'"
                            @click="handleCancel"
                            class="modal-btn cancel-btn">
                        {{ cancelText }}
                    </button>
                    <button @click="handleConfirm"
                            class="modal-btn"
                            :class="confirmButtonClass">
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const isVisible = ref(false);
const title = ref('');
const message = ref('');
const details = ref<Record<string, any> | null>(null);
const confirmText = ref('OK');
const cancelText = ref('Atcelt');
const modalType = ref<'confirm' | 'alert' | 'success' | 'error'>('confirm');
let resolvePromise: ((value: boolean | void) => void) | null = null;

const modalTypeClass = computed(() => {
    switch (modalType.value) {
        case 'success': return 'modal-success';
        case 'error': return 'modal-error';
        default: return '';
    }
});

const confirmButtonClass = computed(() => {
    switch (modalType.value) {
        case 'success': return 'success-btn';
        case 'error': return 'error-btn';
        default: return 'confirm-btn';
    }
});

const showConfirm = (options: {
    title?: string;
    message: string;
    details?: Record<string, any>;
    confirmText?: string;
    cancelText?: string;
}): Promise<boolean> => {
    title.value = options.title || 'Apstiprinājums';
    message.value = options.message;
    details.value = options.details || null;
    confirmText.value = options.confirmText || 'Jā';
    cancelText.value = options.cancelText || 'Nē';
    modalType.value = 'confirm';
    isVisible.value = true;

    return new Promise((resolve) => {
        resolvePromise = resolve;
    });
};

const showAlert = (options: {
    title?: string;
    message: string;
    type?: 'alert' | 'success' | 'error';
    confirmText?: string;
}): Promise<void> => {
    title.value = options.title || 'Paziņojums';
    message.value = options.message;
    confirmText.value = options.confirmText || 'OK';
    modalType.value = options.type || 'alert';
    isVisible.value = true;

    return new Promise((resolve) => {
        resolvePromise = resolve;
    });
};

const handleConfirm = () => {
    isVisible.value = false;
    if (resolvePromise) {
        if (modalType.value === 'confirm') {
            resolvePromise(true);
        } else {
            resolvePromise();
        }
        resolvePromise = null;
    }
};

const handleCancel = () => {
    isVisible.value = false;
    if (resolvePromise) {
        resolvePromise(false);
        resolvePromise = null;
    }
};

const closeModal = () => {
    if (modalType.value === 'confirm') {
        handleCancel();
    } else {
        handleConfirm();
    }
};

defineExpose({
    showConfirm,
    showAlert
});
</script>

<style scoped>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        animation: fadeIn 0.2s ease-out;
    }

    .modal-container {
        background: white;
        border-radius: 1rem;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        animation: slideUp 0.3s ease-out;
    }

    .modal-success {
        border-top: 4px solid #10b981;
    }

    .modal-error {
        border-top: 4px solid #ef4444;
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem 1.5rem 0 1.5rem;
    }

    .modal-icon {
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon {
        width: 1.5rem;
        height: 1.5rem;
        color: #ff8c42;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .modal-body {
        padding: 1rem 1.5rem;
    }

    .modal-message {
        color: #4b5563;
        line-height: 1.5;
        margin: 0;
        white-space: pre-line;
    }

    .modal-details {
        margin-top: 1rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem 0;
        font-size: 0.875rem;
    }

    .detail-label {
        font-weight: 500;
        color: #6b7280;
    }

    .detail-value {
        color: #111827;
        font-weight: 500;
    }

    .modal-footer {
        padding: 1rem 1.5rem 1.5rem 1.5rem;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .modal-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .cancel-btn {
        background: #f3f4f6;
        color: #6b7280;
    }

        .cancel-btn:hover {
            background: #e5e7eb;
        }

    .confirm-btn {
        background: #ff8c42;
        color: white;
    }

        .confirm-btn:hover {
            background: #e65c00;
            transform: translateY(-1px);
        }

    .success-btn {
        background: #10b981;
        color: white;
    }

        .success-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

    .error-btn {
        background: #ef4444;
        color: white;
    }

        .error-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>
