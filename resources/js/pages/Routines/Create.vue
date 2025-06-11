<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    exercises: {
        type: Array,
        required: true,
        default: () => []
    },
    weekDays: {
        type: Array,
        required: true,
        default: () => []
    }
});

const form = ref({
    name: '',
    description: '',
    is_public: false,
    days: props.weekDays.map(day => ({
        day_number: day.id,
        day_name: day.name,
        exercises: []
    }))
});

const addExerciseToDay = (day) => {
    day.exercises.push({
        id: null,
        sets: 3,
        reps: 10,
        rest_seconds: 60,
        notes: ''
    });
};

const submit = () => {
    const exercises = form.value.days
        .flatMap(day => 
            day.exercises
                .filter(ex => ex.id)
                .map(ex => ({
                    id: ex.id,
                    day_number: day.day_number,
                    sets: ex.sets,
                    reps: ex.reps,
                    rest_seconds: ex.rest_seconds,
                    notes: ex.notes
                }))
        );
    
    router.post(route('routines.store'), {
        name: form.value.name,
        description: form.value.description,
        is_public: form.value.is_public,
        exercises
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Create Routine" />
        
        <div class="min-h-screen py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                    <div class="p-8 border-b border-gray-200">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Create New Routine</h1>
                        <p class="text-gray-600">Build your custom workout plan</p>
                    </div>
                    
                    <form @submit.prevent="submit" class="p-8">
  
                        <!-- Routine details -->
<div class="routine-details">
  <h2>Routine Details</h2>

  <div class="form-group">
    <label for="routine-name">Routine Name</label>
    <input
      id="routine-name"
      v-model="form.name"
      type="text"
      placeholder="e.g., Full Body Strength"
      required
    />
  </div>

  <div class="form-group">
    <label for="routine-description">Description</label>
    <textarea
      id="routine-description"
      v-model="form.description"
      rows="3"
      placeholder="Describe your routine goals"
    ></textarea>
  </div>

  <div class="form-group checkbox-group">
    <input
      id="is-public"
      type="checkbox"
      v-model="form.is_public"
    />
    <label for="is-public">Make this routine public</label>
  </div>
</div>

                        
<!-- Weekly plan -->
<div class="weekly-plan">
  <h2>Weekly Plan</h2>

  <div class="day-grid">
    <div class="day-card" v-for="day in form.days" :key="day.day_number">
      <div class="day-header">
        <h3>{{ day.day_name }}</h3>
        <button type="button" @click="addExerciseToDay(day)" class="add-btn">
          ＋ Add
        </button>
      </div>

      <div v-for="(exercise, index) in day.exercises" :key="index" class="exercise-card">
        <label>Exercise</label>
        <select v-model="exercise.id" required>
          <option :value="null">Select</option>
          <option v-for="ex in exercises" :key="ex.id" :value="ex.id">
            {{ ex.name }} ({{ ex.muscle_group }})
          </option>
        </select>

        <div class="input-row">
          <div>
            <label>Sets</label>
            <input type="number" v-model.number="exercise.sets" min="1" required />
          </div>
          <div>
            <label>Reps</label>
            <input type="number" v-model.number="exercise.reps" min="1" required />
          </div>
          <div>
            <label>Rest (s)</label>
            <input type="number" v-model.number="exercise.rest_seconds" min="0" />
          </div>
        </div>

        <label>Notes</label>
        <textarea v-model="exercise.notes" rows="2" placeholder="Tips or variations"></textarea>
      </div>
    </div>
  </div>
</div>

                        
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                class="px-6 py-3 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition"
                            >
                                Save Routine
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.weekly-plan {
  margin-bottom: 3rem;
}

.weekly-plan h2 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid #ddd;
  padding-bottom: 0.5rem;
  color: #333;
}

.day-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.day-card {
  flex: 1 1 calc(33.333% - 20px);
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
  max-width: 481px;
}

.day-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.day-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #444;
}

.add-btn {
  font-size: 0.9rem;
  color: #f25c1e;
  background: none;
  border: none;
  cursor: pointer;
}

.add-btn:hover {
  color: #d94d14;
}

.exercise-card {
  margin-bottom: 1rem;
  background-color: #f9f9f9;
  padding: 12px;
  border-radius: 8px;
  border: 1px solid #e2e2e2;
}

.exercise-card label {
  font-size: 0.85rem;
  color: #555;
  display: block;
  margin-bottom: 4px;
}

.exercise-card select,
.exercise-card input,
.exercise-card textarea {
  width: 100%;
  padding: 6px 8px;
  margin-bottom: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 0.95rem;
}

.input-row {
  display: flex;
  gap: 10px;
}

.input-row > div {
  flex: 1;
}

.routine-details {
  margin-bottom: 3rem;
}

.routine-details h2 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid #ddd;
  padding-bottom: 0.5rem;
  color: #333;
}

.form-group {
  margin-bottom: 1.5rem;
  max-width: 400px;
}

.form-group label {
  display: block;
  font-size: 0.95rem;
  color: #444;
  margin-bottom: 0.5rem;
}

.form-group input[type="text"],
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 1rem;
  background-color: #fff;
  transition: border 0.2s ease;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #f25c1e;
  outline: none;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #f25c1e;
}

</style>