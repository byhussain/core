<?php

namespace SmartTill\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CoreInstallCommand extends Command
{
    protected $signature = 'core:install';

    protected $description = 'Install SMART TiLL core package';

    public function handle(): int
    {
        if (! class_exists(\App\Models\Store::class)) {
            $this->error('Missing required model: App\\Models\\Store');

            return self::FAILURE;
        }

        if (! Schema::hasTable('stores')) {
            $this->error('Missing required table: stores');

            return self::FAILURE;
        }

        $this->info('Running migrations...');

        $exitCode = Artisan::call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->line(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->error('Migration failed during core install.');

            return self::FAILURE;
        }

        $this->info('Core package installed successfully.');

        return self::SUCCESS;
    }
}
