<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    title?: string;
    description?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="auth-layout">
        <div class="auth-container">
            <!-- Header Section - Centered -->
            <div class="auth-header">
                <h1 v-if="title" class="auth-title">{{ title }}</h1>
                <p v-if="description" class="auth-description">{{ description }}</p>
            </div>

            <!-- Content Slot -->
            <div class="auth-content">
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
    /* Layout Container */
    .auth-layout {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
        padding: 1rem;
    }

    .auth-container {
        width: 100%;
        max-width: 28rem;
    }

    /* Header Section - Centered */
    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.75rem;
        letter-spacing: -0.025em;
    }

    .auth-description {
        font-size: 0.875rem;
        color: #6b7280;
        line-height: 1.5;
        max-width: 28rem;
        margin: 0 auto;
    }

    /* Content Section */
    .auth-content {
        background-color: #ffffff;
        border-radius: 1rem;
        border: 1px solid #f3f4f6;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    /* Responsive Design */
    @media (max-width: 640px) {
        .auth-container {
            max-width: 100%;
        }

        .auth-title {
            font-size: 1.5rem;
        }

        .auth-description {
            font-size: 0.8125rem;
            padding: 0 1rem;
        }

        .auth-header {
            margin-bottom: 1.5rem;
        }
    }

</style>
