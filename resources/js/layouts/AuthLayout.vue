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
            <!-- virsraksts — tukšs ja title nav padots -->
            <div class="auth-header" v-if="title || description">
                <h1 v-if="title" class="auth-title">{{ title }}</h1>
                <p v-if="description" class="auth-description">{{ description }}</p>
            </div>
            <!-- saturs no login/register -->
            <div class="auth-content">
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
    .auth-layout {
        min-height: 100vh;
        display: flex;
        align-items: stretch;
        justify-content: stretch;
        background: #0d0d0d;
        padding: 0;
    }

    /* nav max-width — login/register kontrolē platumu paši */
    .auth-container {
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.5rem;
    }

    .auth-description {
        font-size: 0.875rem;
        color: #666;
    }

    /* auth-content aizpilda visu platumu */
    .auth-content {
        flex: 1;
        display: flex;
        width: 100%;
        overflow: hidden;
    }

        /* slot satura wrapper — pilns platums */
        .auth-content > * {
            width: 100%;
        }
</style>
