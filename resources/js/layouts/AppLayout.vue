<script setup lang="ts">
import Navbar from '@/components/Navbar.vue';
import Foot from '@/components/Foot.vue';
import Modal from '@/components/Modal.vue';
import { useModal } from '@/composables/useModal';
import { usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

interface Props {
    title?: string;
    description?: string;
}

defineProps<Props>();

const page = usePage();
const { modalRef, confirm } = useModal();

const activeSession = computed(() => (page.props as any).activeWorkoutSession as {
    id: number; name: string; started_at: string;
} | null);

const isOnWorkoutPage = computed(() => (page as any).component === 'Workout/FreeWorkout');

const timer = ref(0);
let timerInterval: ReturnType<typeof setInterval> | null = null;

const pad = (n: number) => n.toString().padStart(2, '0');
const formattedTime = computed(() => {
    const h = Math.floor(timer.value / 3600);
    const m = Math.floor((timer.value % 3600) / 60);
    const s = timer.value % 60;
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
});

const stopTimer = () => {
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
};

const startTimer = () => {
    stopTimer();
    if (activeSession.value?.started_at) {
        timer.value = Math.floor((Date.now() - new Date(activeSession.value.started_at).getTime()) / 1000);
    }
    timerInterval = setInterval(() => timer.value++, 1000);
};

const cancelWorkout = async () => {
    if (!activeSession.value) return;
    const ok = await confirm({
        title: 'Atcelt treniņu?',
        message: 'Vai tiešām vēlies atcelt treniņu? Visi dati tiks zaudēti.',
        confirmText: 'Jā, atcelt',
        cancelText: 'Nē',
    });
    if (!ok) return;
    await axios.post(`/workout/${activeSession.value.id}/cancel`);
    router.reload({ only: ['activeWorkoutSession'] });
};

watch(
    () => activeSession.value,
    (val) => {
        if (val && !isOnWorkoutPage.value) startTimer();
        else stopTimer();
    },
    { immediate: false }
);

onMounted(() => {
    if (activeSession.value && !isOnWorkoutPage.value) startTimer();
});

onUnmounted(() => stopTimer());
</script>

<template>
    <div class="app-layout">
        <Navbar />

        <!-- Main Content -->
        <main class="app-main" :class="{ 'has-workout-bar': activeSession && !isOnWorkoutPage }">
            <div class="app-container">
                <!-- Optional Header Section -->
                <div v-if="title || description" class="page-header">
                    <h1 v-if="title" class="page-title">{{ title }}</h1>
                    <p v-if="description" class="page-description">{{ description }}</p>
                </div>

                <!-- Page Content -->
                <div class="page-content">
                    <slot v-bind="$attrs" />
                </div>
            </div>
        </main>

        <!-- Aktīvā treniņa josla -->
        <Teleport to="body">
            <div v-if="activeSession && !isOnWorkoutPage" class="workout-banner">
                <div class="workout-banner-left">
                    <span class="workout-banner-dot"></span>
                    <span class="workout-banner-name">{{ activeSession.name }}</span>
                    <span class="workout-banner-timer">{{ formattedTime }}</span>
                </div>
                <div class="workout-banner-right">
                    <a :href="'/workout/free'" class="workout-banner-btn workout-banner-resume">
                        Turpināt
                    </a>
                    <button @click="cancelWorkout" class="workout-banner-btn workout-banner-cancel">
                        Atcelt
                    </button>
                </div>
            </div>
        </Teleport>

        <Foot />
        <Modal ref="modalRef" />
    </div>
</template>

<style scoped>
/* Aktīvā treniņa josla */
.workout-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 1.5rem;
    background: linear-gradient(135deg, #ff8c42 0%, #e65c00 100%);
    box-shadow: 0 -2px 16px rgba(230, 92, 0, 0.35);
    gap: 1rem;
}

.workout-banner-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    min-width: 0;
}

.workout-banner-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #fff;
    flex-shrink: 0;
    animation: blink 1.4s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.workout-banner-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.workout-banner-timer {
    font-family: monospace;
    font-size: 0.95rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    flex-shrink: 0;
}

.workout-banner-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.workout-banner-btn {
    padding: 0.375rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: all 0.15s;
}

.workout-banner-resume {
    background: white;
    color: #e65c00;
}

.workout-banner-resume:hover {
    background: #fff7ed;
}

.workout-banner-cancel {
    background: rgba(0, 0, 0, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.workout-banner-cancel:hover {
    background: rgba(0, 0, 0, 0.35);
}

.has-workout-bar {
    padding-bottom: calc(2rem + 44px);
}

@media (max-width: 480px) {
    .workout-banner { padding: 0.5rem 0.875rem; }
    .workout-banner-name { max-width: 120px; font-size: 0.8rem; }
    .workout-banner-timer { font-size: 0.85rem; }
    .workout-banner-btn { font-size: 0.75rem; padding: 0.3rem 0.65rem; }
}

    /* Layout Structure */
    .app-layout {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background-color: #f9fafb;
    }

    /* Main Content Area */
    .app-main {
        flex: 1;
        padding: 2rem 1.5rem;
    }

    /* Container - Wider */
    .app-container {
        /*max-width: 1400px;*/
        margin: 0 auto;
        width: 100%;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 2rem;
        animation: fadeInUp 0.4s ease-out;
    }

    .page-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.5rem;
        letter-spacing: -0.025em;
    }

    .page-description {
        font-size: 0.875rem;
        color: #6b7280;
        line-height: 1.5;
    }

    /* Page Content - Full Width */
    .page-content {
        background-color: #ffffff;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        animation: fadeIn 0.5s ease-out;
        width: 100%;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    /* Responsive Design */
    @media (max-width: 1440px) {
        .app-container {
            max-width: 1200px;
        }
    }

    @media (max-width: 1024px) {
        .app-container {
            max-width: 100%;
        }

        .app-main {
            padding: 1.5rem 1rem;
        }
    }

    @media (max-width: 768px) {
        .app-main {
            padding: 1rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .page-description {
            font-size: 0.8125rem;
        }

        .page-content {
            padding: 1rem;
            border-radius: 0.75rem;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }
    }

    @media (max-width: 640px) {
        .app-main {
            padding: 0.75rem;
        }

        .page-content {
            padding: 0.75rem;
            border-radius: 0.5rem;
        }

        .page-title {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 480px) {
        .app-main {
            padding: 0.5rem;
        }

        .page-content {
            padding: 0.625rem;
        }

        .page-title {
            font-size: 1.15rem;
        }
    }

    /* Optional: Smooth transitions */
    .page-content,
    .page-title,
    .page-description {
        transition: all 0.2s ease;
    }
</style>
