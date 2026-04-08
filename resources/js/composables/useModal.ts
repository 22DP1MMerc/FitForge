import { ref } from 'vue';

interface ConfirmOptions {
    title?: string;
    message: string;
    details?: Record<string, any>;
    confirmText?: string;
    cancelText?: string;
}

interface AlertOptions {
    title?: string;
    message: string;
    type?: 'alert' | 'success' | 'error';
    confirmText?: string;
}

export const useModal = () => {
    const modalRef = ref<any>(null);

    const confirm = (options: ConfirmOptions): Promise<boolean> => {
        if (modalRef.value) {
            return modalRef.value.showConfirm(options);
        }
        return Promise.reject('Modal component not mounted');
    };

    const alert = (options: AlertOptions): Promise<void> => {
        if (modalRef.value) {
            return modalRef.value.showAlert(options);
        }
        return Promise.reject('Modal component not mounted');
    };

    const success = (message: string, title: string = 'Veiksmīgi!'): Promise<void> => {
        return alert({
            title,
            message,
            type: 'success',
            confirmText: 'Labi'
        });
    };

    const error = (message: string, title: string = 'Kļūda!'): Promise<void> => {
        return alert({
            title,
            message,
            type: 'error',
            confirmText: 'Labi'
        });
    };

    return {
        modalRef,
        confirm,
        alert,
        success,
        error
    };
};
