<script setup>
    import { ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';

    const props = defineProps({
        availableExercises: {
            type: Array,
            default: () => []
        }
    });

    const workoutName = ref('Brīvais treniņš');
    const selectedExercises = ref([]);
    const isLoading = ref(false);

    const addExercise = (exercise) => {
        if (!selectedExercises.value.find(e => e.id === exercise.id)) {
            selectedExercises.value.push({
                id: exercise.id,
                name: exercise.name,
                muscle_group: exercise.muscle_group,
                sets: 3,
                reps: 10
            });
        }
    };

    const removeExercise = (index) => {
        selectedExercises.value.splice(index, 1);
    };

    const goBack = () => {
        router.visit(route('dashboard'));
    };

    const startWorkout = () => {
        if (selectedExercises.value.length === 0) {
            alert('Lūdzu, pievieno vismaz vienu vingrinājumu!');
            return;
        }

        isLoading.value = true;

        // Simulējam treniņa sākšanu ar nelielu aizkavi
        setTimeout(() => {
            isLoading.value = false;

            // Piemēram, varam izveidot treniņa sesiju un pārvirzīt uz treniņa lapu
            const workoutData = {
                name: workoutName.value,
                exercises: selectedExercises.value,
                started_at: new Date().toISOString()
            };

            // Saglabājam treniņa datus localStorage (pagaidu risinājums)
            localStorage.setItem('currentWorkout', JSON.stringify(workoutData));

            // Parādām veiksmīgu ziņu
            alert(`✅ Treniņš "${workoutName.value}" veiksmīgi sākts!\n\n` +
                `📋 Vingrinājumi: ${selectedExercises.value.length}\n` +
                `⏰ Sākts: ${new Date().toLocaleTimeString('lv-LV')}\n\n` +
                `Treniņa reģistrēšanas funkcionalitāte tiks pilnveidota drīzumā!`);

            // Atgriežamies uz dashboard, bet ar treniņa informāciju
            goBack();
        }, 1500);
    };

    // Pievieno dažus vingrinājumus automātiski, ja nav izvēlēts neviens
    const addSampleExercises = () => {
        if (props.availableExercises.length > 0 && selectedExercises.value.length === 0) {
            // Pievieno pirmos 3 vingrinājumus kā piemēru
            props.availableExercises.slice(0, 3).forEach(exercise => {
                addExercise(exercise);
            });
        }
    };

    // Izsaucam, kad komponents ielādējas
    addSampleExercises();
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Header -->
                <div class="mb-8">
                    <button @click="goBack" class="flex items-center text-orange-500 hover:text-orange-700 mb-4 group">
                        <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Atpakaļ uz paneli
                    </button>

                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Brīvā treniņa sākšana</h1>
                            <p class="text-gray-600">Izveido pielāgotu treniņu bez rutīnas</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-sm font-medium rounded-full">
                                {{ selectedExercises.length }} vingrinājumi
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                    <div class="p-8">
                        <!-- Treniņa nosaukums -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Treniņa nosaukums
                                </span>
                            </label>
                            <input v-model="workoutName"
                                   type="text"
                                   class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                                   placeholder="Ievadi treniņa nosaukumu...">
                        </div>

                        <!-- Divas kolonnas: pieejamie un izvēlētie vingrinājumi -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                            <!-- Pievienošanas sadaļa -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-xl font-semibold text-gray-800">Pieejamie vingrinājumi</h2>
                                    <span class="text-sm text-gray-500">{{ availableExercises.length }} pieejami</span>
                                </div>

                                <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                                    <div v-for="exercise in availableExercises" :key="exercise.id"
                                         class="border border-gray-200 rounded-lg p-4 hover:border-orange-300 hover:bg-orange-50 transition-all cursor-pointer"
                                         @click="addExercise(exercise)">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h3 class="font-medium text-gray-900">{{ exercise.name }}</h3>
                                                <p class="text-sm text-gray-500">{{ exercise.muscle_group }}</p>
                                            </div>
                                            <div class="flex items-center">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded mr-2">
                                                    Pievienot
                                                </span>
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Izvēlēto vingrinājumu sadaļa -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-xl font-semibold text-gray-800">Izvēlētie vingrinājumi</h2>
                                    <button v-if="selectedExercises.length > 0"
                                            @click="selectedExercises = []"
                                            class="text-sm text-red-600 hover:text-red-800">
                                        Noņemt visus
                                    </button>
                                </div>

                                <div v-if="selectedExercises.length > 0" class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                                    <div v-for="(exercise, index) in selectedExercises" :key="exercise.id"
                                         class="border-2 border-orange-200 rounded-lg p-4 bg-orange-50">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-3">
                                                    <span class="flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-700 rounded-full font-semibold mr-3">
                                                        {{ index + 1 }}
                                                    </span>
                                                    <div>
                                                        <h3 class="font-medium text-gray-900">{{ exercise.name }}</h3>
                                                        <p class="text-sm text-gray-500">{{ exercise.muscle_group }}</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 gap-4 ml-11">
                                                    <div>
                                                        <label class="block text-sm text-gray-600 mb-1">Seti</label>
                                                        <input v-model="exercise.sets" type="number" min="1" max="10"
                                                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-orange-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm text-gray-600 mb-1">Atkārtojumi</label>
                                                        <input v-model="exercise.reps" type="number" min="1" max="50"
                                                               class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-orange-500">
                                                    </div>
                                                </div>
                                            </div>
                                            <button @click.stop="removeExercise(index)"
                                                    class="ml-4 p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500 mb-2">Nav izvēlētu vingrinājumu</p>
                                    <p class="text-sm text-gray-400">Klikšķini uz vingrinājumiem, lai tos pievienotu</p>
                                </div>
                            </div>
                        </div>

                        <!-- Informatīva ziņa -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-8">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-blue-800 mb-2">Kā strādā brīvais treniņš?</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-white/50 p-3 rounded-lg">
                                            <h4 class="font-medium text-blue-700 mb-1">1. Izvēlies vingrinājumus</h4>
                                            <p class="text-sm text-blue-600">Klikšķini uz vingrinājumiem, lai tos pievienotu treniņam</p>
                                        </div>
                                        <div class="bg-white/50 p-3 rounded-lg">
                                            <h4 class="font-medium text-blue-700 mb-1">2. Pielāgo parametrus</h4>
                                            <p class="text-sm text-blue-600">Norādi setu un atkārtojumu skaitu katram vingrinājumam</p>
                                        </div>
                                        <div class="bg-white/50 p-3 rounded-lg">
                                            <h4 class="font-medium text-blue-700 mb-1">3. Sāc treniņu</h4>
                                            <p class="text-sm text-blue-600">Nospied pogu "Sākt brīvo treniņu", lai sāktu treniņu</p>
                                        </div>
                                        <div class="bg-white/50 p-3 rounded-lg">
                                            <h4 class="font-medium text-blue-700 mb-1">4. Reģistrē progresu</h4>
                                            <p class="text-sm text-blue-600">Drīzumā būs iespējams reģistrēt svaru un atkārtojumus</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pogas -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button @click="goBack"
                                    class="flex-1 px-6 py-4 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all duration-200 shadow-sm hover:shadow flex items-center justify-center">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Atpakaļ uz paneli
                            </button>

                            <button @click="startWorkout" :disabled="isLoading || selectedExercises.length === 0"
                                    :class="[
                                    'flex-1 px-6 py-4 font-semibold rounded-xl transition-all duration-200 shadow-md hover:shadow-lg flex items-center justify-center' ,
                                    (!isLoading && selectedExercises.length>
                                0)
                                ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white hover:from-orange-600 hover:to-red-600'
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                ]">
                                <span v-if="isLoading" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sagatavo...
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Sākt brīvo treniņu
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Apakšējā informācija -->
                <div class="text-center">
                    <p class="text-gray-500 text-sm">
                        Brīvā treniņa funkcionalitāte tiek pilnveidota. Drīzumā būs pieejama pilna treniņu reģistrēšanas sistēma!
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
</style>
