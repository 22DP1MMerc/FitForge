<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\WorkoutSessionExercise;

return new class extends Migration
{
    public function up()
    {
        // Nedefinējam jaunas tabulas, bet apstrādājam esošos datus
        $exercises = WorkoutSessionExercise::whereNotNull('weights_used')->get();
        
        \Log::info('Starting weights_used format conversion. Found ' . $exercises->count() . ' exercises with weights.');
        
        $convertedCount = 0;
        $skippedCount = 0;
        
        foreach ($exercises as $exercise) {
            try {
                $weights = $exercise->weights_used;
                $reps = $exercise->reps_completed ?? [];
                
                \Log::info("Processing exercise ID: {$exercise->id}", [
                    'weights_type' => gettype($weights),
                    'weights_data' => $weights,
                    'reps_data' => $reps
                ]);
                
                // Ja weights_used jau ir tukšs, izlaižam
                if (empty($weights) || !is_array($weights)) {
                    $skippedCount++;
                    continue;
                }
                
                $newWeights = [];
                $hasSimpleNumbers = false;
                
                foreach ($weights as $index => $weight) {
                    // Ja jau ir masīvs ar 'weight' atslēgu, atstājam kā ir
                    if (is_array($weight) && isset($weight['weight'])) {
                        $newWeights[$index] = $weight;
                    } 
                    // Ja ir vienkāršs skaitlis, pārveidojam uz masīvu
                    else if (is_numeric($weight)) {
                        $hasSimpleNumbers = true;
                        $newWeights[$index] = [
                            'weight' => floatval($weight),
                            'reps' => $reps[$index] ?? 0,
                            'original_format' => 'numeric',
                            'converted_at' => now()->toISOString()
                        ];
                    }
                    // Ja ir kaut kas cits, atstājam kā ir
                    else {
                        $newWeights[$index] = $weight;
                    }
                }
                
                // Ja bija vienkārši skaitļi, atjauninam datubāzē
                if ($hasSimpleNumbers) {
                    $exercise->weights_used = $newWeights;
                    $exercise->save();
                    $convertedCount++;
                    
                    \Log::info("Converted exercise ID: {$exercise->id}", [
                        'old_weights' => $weights,
                        'new_weights' => $newWeights
                    ]);
                } else {
                    $skippedCount++;
                }
                
            } catch (\Exception $e) {
                \Log::error("Error converting exercise ID: {$exercise->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        \Log::info("Conversion completed. Converted: {$convertedCount}, Skipped: {$skippedCount}");
    }
    
    public function down()
    {
        \Log::info('Starting rollback of weights_used format conversion.');
        
        $exercises = WorkoutSessionExercise::whereNotNull('weights_used')->get();
        
        $rolledBackCount = 0;
        $skippedCount = 0;
        
        foreach ($exercises as $exercise) {
            try {
                $weights = $exercise->weights_used;
                
                if (empty($weights) || !is_array($weights)) {
                    $skippedCount++;
                    continue;
                }
                
                $simpleWeights = [];
                $hasArrays = false;
                
                foreach ($weights as $index => $weight) {
                    // Ja ir masīvs ar 'weight' atslēgu, pārveidojam atpakaļ uz skaitli
                    if (is_array($weight) && isset($weight['weight'])) {
                        $hasArrays = true;
                        $simpleWeights[$index] = $weight['weight'];
                    } 
                    // Ja jau ir skaitlis, atstājam kā ir
                    else {
                        $simpleWeights[$index] = $weight;
                    }
                }
                
                // Ja bija masīvi, atjauninam atpakaļ
                if ($hasArrays) {
                    $exercise->weights_used = $simpleWeights;
                    $exercise->save();
                    $rolledBackCount++;
                    
                    \Log::info("Rolled back exercise ID: {$exercise->id}");
                } else {
                    $skippedCount++;
                }
                
            } catch (\Exception $e) {
                \Log::error("Error rolling back exercise ID: {$exercise->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        \Log::info("Rollback completed. Rolled back: {$rolledBackCount}, Skipped: {$skippedCount}");
    }
};
