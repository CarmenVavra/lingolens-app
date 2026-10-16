<?php

namespace App\Support;

/**
 * Small helpers around the LingoLens configuration.
 */
class LingoLens
{
    /**
     * Demo mode replaces OCR and AI correction with the sample texts. It is
     * active when configured explicitly or when an API key is missing.
     */
    public static function demoMode(): bool
    {
        $configured = config('lingolens.demo_mode');

        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        return blank(config('services.google.vision_key')) || blank(config('services.openai.key'));
    }

    /**
     * Validity of the invitation link in days (rounded up).
     */
    public static function invitationDays(): int
    {
        return max(1, (int) ceil((int) config('auth.passwords.invitations.expire') / 1440));
    }

    public static function daysUntilMatura(): int
    {
        $date = now()->parse((string) config('lingolens.matura_date'))->startOfDay();

        return max(0, (int) now()->startOfDay()->diffInDays($date, false));
    }
}
