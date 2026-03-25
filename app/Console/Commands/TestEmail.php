<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'mail:test';
    protected $description = 'Test email configuration';

    public function handle()
    {
        try {
            Mail::raw('Test email from Laravel at ' . now(), function ($message) {
                $message->to('fitforgemail@gmail.com')
                        ->subject('Test Email');
            });
            $this->info('Email sent successfully!');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
