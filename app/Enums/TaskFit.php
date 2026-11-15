<?php

namespace App\Enums;

/**
 * How well a text answers the task prompt ("Aufgabenstellung").
 */
enum TaskFit: string
{
    case Fits = 'fits';
    case Partly = 'partly';
    case OffTopic = 'off_topic';

    /**
     * Map the verdict of the AI ("passt", "teilweise", "verfehlt") to the enum.
     * Unknown answers count as "partly" – never claim a perfect fit by mistake.
     */
    public static function fromAi(string $value): self
    {
        return match (mb_strtolower(trim($value))) {
            'passt', 'fits', 'erfüllt' => self::Fits,
            'verfehlt', 'off_topic', 'themenverfehlung' => self::OffTopic,
            default => self::Partly,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Fits => 'Passt zur Aufgabe',
            self::Partly => 'Teilweise erfüllt',
            self::OffTopic => 'Themenverfehlung',
        };
    }

    /**
     * CSS modifier of the verdict badge.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Fits => 'fits',
            self::Partly => 'partly',
            self::OffTopic => 'off-topic',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Fits => 'check',
            self::Partly, self::OffTopic => 'triangle-alert',
        };
    }
}
