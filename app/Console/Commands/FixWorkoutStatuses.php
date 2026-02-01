<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkoutSession;
use Carbon\Carbon;

class FixWorkoutStatuses extends Command
{
    protected $signature = 'workouts:fix-statuses';
    protected $description = 'Fix workout session statuses that are stuck as active';

    public function handle()
    {
        $this->info('Checking for stuck workout sessions...');
        
        // Atrod visus aktīvos treniņus, kas ir vecāki par 24h
        $stuckSessions = WorkoutSession::where('status', 'active')
            ->where('started_at', '<', Carbon::now()->subDay())
            ->get();
        
        $this->info("Found {$stuckSessions->count()} stuck sessions");
        
        if ($stuckSessions->count() > 0) {
            foreach ($stuckSessions as $session) {
                $hoursOld = Carbon::now()->diffInHours($session->started_at);
                
                // Automātiski pabeidz vecos treniņus
                $session->update([
                    'status' => 'completed',
                    'ended_at' => $session->started_at->addHour(), // Pievieno 1h ilgumu
                    'duration_minutes' => 60,
                    'notes' => 'Automātiski pabeigts (sistēmas labošana)'
                ]);
                
                $this->line("Fixed session #{$session->id}: {$session->name} ({$hoursOld}h old)");
            }
            
            $this->info('All stuck sessions have been fixed!');
        } else {
            $this->info('No stuck sessions found.');
        }
        
        return 0;
    }
}
