<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:init')]
#[Description('Initialize the application database and seed data')]
class InitializeApp extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->components->info('Initializing application...');

        return $this->call('migrate', [
            '--seed' => true,
        ]);
    }
}
