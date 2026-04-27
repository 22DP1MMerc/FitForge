<script setup lang="ts">
import Navbar from '@/components/Navbar.vue';
import Foot from '@/components/Foot.vue';

interface Props {
    title?: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="app-layout">
        <Navbar />

        <!-- Main Content -->
        <main class="app-main">
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

        <Foot />
    </div>
</template>

<style scoped>
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

    /* Optional: Smooth transitions */
    .page-content,
    .page-title,
    .page-description {
        transition: all 0.2s ease;
    }
</style>
