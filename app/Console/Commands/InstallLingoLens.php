<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * One-time setup on the server (also safe to run again after an update):
 * creates the SQLite file if needed and runs the migrations.
 *
 * On hosting without SSH it can be started once as a scheduled task.
 */
class InstallLingoLens extends Command
{
    protected $signature = 'lingolens:install';

    protected $description = 'Prepare the database and storage for LingoLens AI (idempotent)';

    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->error('APP_KEY fehlt in der .env. Erzeuge ihn lokal mit "php artisan key:generate --show".');

            return self::FAILURE;
        }

        if (config('database.default') === 'sqlite') {
            $path = (string) config('database.connections.sqlite.database');

            if ($path !== ':memory:' && ! File::exists($path)) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, '');
                $this->info("SQLite-Datenbank angelegt: {$path}");
            }
        }

        foreach (['app/private/scans', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
            File::ensureDirectoryExists(storage_path($directory));
        }

        $this->call('migrate', ['--force' => true]);

        $this->info('LingoLens AI ist bereit.');

        return self::SUCCESS;
    }
}
