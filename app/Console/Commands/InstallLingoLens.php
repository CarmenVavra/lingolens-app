<?php

namespace App\Console\Commands;

use App\Support\LingoLens;
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

        if (filled(config('lingolens.admin_email'))) {
            $this->call('lingolens:admin');
        } else {
            $this->warn('LINGOLENS_ADMIN_EMAIL fehlt in der .env – ohne Admin können keine Schüler:innen angelegt werden.');
        }

        foreach (['name' => 'LEGAL_NAME', 'street' => 'LEGAL_STREET', 'city' => 'LEGAL_CITY', 'email' => 'LEGAL_EMAIL'] as $key => $env) {
            if (blank(config('legal.operator.'.$key))) {
                $this->warn("{$env} fehlt in der .env – Impressum und Datenschutzerklärung sind unvollständig.");
            }
        }

        $this->reportAiMode();

        $this->info('LingoLens AI ist bereit.');

        return self::SUCCESS;
    }

    /**
     * Shows whether the real OpenAI correction or the demo mode is active.
     */
    private function reportAiMode(): void
    {
        $configured = config('lingolens.demo_mode');
        $explicitDemo = $configured !== null && $configured !== '' && filter_var($configured, FILTER_VALIDATE_BOOLEAN);

        if (LingoLens::demoMode()) {
            $this->warn($explicitDemo
                ? 'KI-Modus: DEMO (LINGOLENS_DEMO_MODE=true) – Schüler:innen bekommen nur Beispiel-Korrekturen.'
                : 'KI-Modus: DEMO – OPENAI_API_KEY fehlt in der .env, Schüler:innen bekommen nur Beispiel-Korrekturen.');

            return;
        }

        if (blank(config('services.openai.key'))) {
            $this->error('KI-Modus: OpenAI, aber OPENAI_API_KEY fehlt in der .env – jeder Scan schlägt fehl.');

            return;
        }

        $this->info('KI-Modus: OpenAI (Korrektur: '.config('lingolens.openai_model').', Texterkennung: '.LingoLens::ocrLabel().').');
    }
}
