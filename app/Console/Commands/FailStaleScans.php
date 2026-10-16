<?php

namespace App\Console\Commands;

use App\Enums\ScanStatus;
use App\Models\Scan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Marks scans as failed that have been "processing" for too long, e.g. when
 * the web server stopped the analysis or the cron job was not running.
 * Students then see the error screen instead of waiting forever.
 */
class FailStaleScans extends Command
{
    protected $signature = 'scans:fail-stale {--minutes=15 : Minutes without progress before a scan counts as stuck}';

    protected $description = 'Mark scans that are stuck in "processing" as failed';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('minutes')));
        $count = 0;

        Scan::query()
            ->where('status', ScanStatus::Processing)
            ->where('updated_at', '<', $cutoff)
            ->each(function (Scan $scan) use (&$count) {
                if ($scan->file_path) {
                    Storage::disk('local')->delete($scan->file_path);
                }

                $scan->update([
                    'status' => ScanStatus::Failed,
                    'failure_reason' => 'Die Analyse hat zu lange gedauert. Bitte versuche es noch einmal.',
                    'file_path' => null,
                ]);
                $count++;
            });

        $this->info("{$count} hängende Scans als fehlgeschlagen markiert.");

        return self::SUCCESS;
    }
}
