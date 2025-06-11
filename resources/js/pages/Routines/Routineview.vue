<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    routines: Array,
});
</script>

<template>
    <AppLayout>
        <Head title="My Routines" />
        
        <div class="routines-page">
            <div class="routines-container">
                <div class="routines-content">
                    <div class="routines-header">
                        <h1>My Routines</h1>
                        <Link href="/routines/create" class="create-button">
                            Create New Routine
                        </Link>
                    </div>
                    
                    <div class="routines-list">
                        <div class="routines-grid">
                            <div v-for="routine in routines" :key="routine.id" class="routine-card">
                                <Link :href="`/routines/${routine.id}`" class="routine-link">
                                    <h2>{{ routine.name }}</h2>
                                    <p class="routine-description">{{ routine.description }}</p>
                                    <div class="routine-meta">
                                        <span class="exercise-count">{{ routine.exercises.length }} exercises</span>
                                        <span v-if="routine.is_public" class="public-tag">Public</span>
                                    </div>
                                </Link>
                            </div>
                            
                            <div v-if="routines.length === 0" class="empty-state">
                                <div class="empty-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p>You don't have any routines yet.</p>
                                <Link href="/routines/create" class="create-button">
                                    Create Your First Routine
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Base Styles */
.routines-page {
    padding: 3rem 0;
    background-color: #f8f9fa;
    min-height: 100vh;
}

.routines-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.routines-content {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
    overflow: hidden;
    padding: 2rem;
}

/* Header Styles */
.routines-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.routines-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

/* Grid Layout */
.routines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Routine Card */
.routine-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
}

.routine-card:hover {
    transform: translateY(-0.25rem);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.routine-link {
    display: block;
    color: inherit;
    text-decoration: none;
    height: 100%;
}

.routine-card h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.75rem 0;
}

.routine-description {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.routine-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}

.exercise-count {
    color: #6b7280;
    font-size: 0.8125rem;
}

.public-tag {
    background: #dcfce7;
    color: #166534;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-icon {
    margin: 0 auto 1rem;
    width: 3rem;
    height: 3rem;
    color: #d1d5db;
}

.empty-state p {
    margin-bottom: 1.5rem;
}

/* Buttons */
.create-button {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background-color: #ea580c;
    color: white;
    border-radius: 0.5rem;
    font-weight: 500;
    text-decoration: none;
    transition: background-color 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.create-button:hover {
    background-color: #c2410c;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .routines-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .routines-grid {
        grid-template-columns: 1fr;
    }
}
</style>