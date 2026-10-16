<?php

namespace App\Enums;

/**
 * Lifecycle of a scan (scans.status).
 */
enum ScanStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Wird analysiert',
            self::Completed => 'Fertig',
            self::Failed => 'Fehlgeschlagen',
        };
    }

    /**
     * CSS modifier for the status tag and icon square.
     */
    public function slug(): string
    {
        return $this->value;
    }
}
