<script setup>
import TextLink from '@/components/TextLink.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    routine: Object,
});

const safeRoutine = computed(() => {
    return props.routine || {
        name: 'Loading...',
        description: '',
        exercises: [],
    };
});

const getExerciseProperty = (exercise, prop) => {
    return exercise[prop] || 'N/A';
};
</script>

<template>
  <AppLayout>
    <Head :title="safeRoutine.name" />
    
    <div class="routine-container">
      <div class="routine-content">
        <TextLink
          :href="route('routines')"
          class="close-button"
        >
          ×
        </TextLink>

        <h1>{{ safeRoutine.name }}</h1>
        <p class="routine-description">{{ safeRoutine.description }}</p>

        <div class="days-wrapper">
          <div
            v-for="day in [1,2,3,4,5,6,7]"
            :key="day"
            class="day-card"
          >
            <h2>
              {{ ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'][day - 1] }}
            </h2>

            <template v-if="safeRoutine.exercises.filter(e => e.pivot?.day_number === day).length">
              <div
                v-for="exercise in safeRoutine.exercises.filter(e => e.pivot?.day_number === day)"
                :key="exercise.id"
                class="exercise-card"
              >
                <div class="exercise-header">
                  <h3>{{ getExerciseProperty(exercise, 'name') }}</h3>
                  <div class="exercise-sets">
                    {{ exercise.pivot?.sets || 0 }} × {{ exercise.pivot?.reps || 0 }}
                    <span v-if="exercise.pivot?.rest_seconds">
                      ({{ Math.floor(exercise.pivot.rest_seconds / 60) }} min rest)
                    </span>
                  </div>
                </div>
                <p class="exercise-description">{{ getExerciseProperty(exercise, 'description') }}</p>
                <div class="exercise-tags">
                  <span class="badge muscle">{{ getExerciseProperty(exercise, 'muscle_group') }}</span>
                  <span class="badge equipment">{{ getExerciseProperty(exercise, 'equipment') }}</span>
                </div>
                <p v-if="exercise.pivot?.notes" class="exercise-notes">
                  Notes: {{ exercise.pivot.notes }}
                </p>
              </div>
            </template>
            <p v-else class="rest-day">Rest day</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* Base Styles */
.routine-container {
  padding: 3rem 0;
  background-color: #f9fafb;
  min-height: 100vh;
}

.routine-content {
  max-width: 72rem;
  margin: 0 auto;
  padding: 0 1rem;
  position: relative;
  background: white;
  border-radius: 1rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
  padding: 2rem;
  border: 1px solid #e5e7eb;
}

/* Close Button */
.close-button {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  font-size: 2rem;
  line-height: 1;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  text-decoration: none;
  transition: all 0.2s ease;
  border-radius: 50%;
}

.close-button:hover {
  color: #ff6b00;
  background-color: #f3f4f6;
  transform: scale(1.1);
}

/* Typography */
h1 {
  font-size: 2rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.5rem;
}

.routine-description {
  color: #6b7280;
  margin-bottom: 2rem;
  font-size: 1.125rem;
}

.day-card h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #ff6b00;
  margin-bottom: 1rem;
}

.exercise-card h3 {
  font-size: 1.125rem;
  font-weight: 500;
  color: #111827;
}

/* Layout */
.days-wrapper {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-top: 2rem;
}

.day-card {
  flex: 1 1 20rem;
  min-width: 18rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.exercises-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.exercise-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  transition: all 0.2s ease;
}

.exercise-card:hover {
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
  transform: translateY(-0.125rem);
}

/* Exercise Details */
.exercise-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.exercise-sets {
  font-size: 0.875rem;
  color: #6b7280;
}

.exercise-description {
  color: #6b7280;
  margin: 0.5rem 0;
}

.exercise-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 9999px;
  text-transform: capitalize;
}

.badge.muscle {
  background-color: #ffe7d6;
  color: #b45309;
}

.badge.equipment {
  background-color: #d1fae5;
  color: #065f46;
}

/* Rest Day */
.rest-day {
  font-size: 0.875rem;
  color: #6b7280;
  font-style: italic;
}

/* Exercise Notes */
.exercise-notes {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px dashed #e5e7eb;
}
</style>