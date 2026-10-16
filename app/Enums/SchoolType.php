<?php

namespace App\Enums;

/**
 * Austrian school types: AHS (academic) and BHS (vocational, e.g. HTL/HAK/HLW).
 */
enum SchoolType: string
{
    case AHS = 'AHS';
    case BHS = 'BHS';

    public function description(): string
    {
        return match ($this) {
            self::AHS => 'Allgemeinbildende höhere Schule',
            self::BHS => 'HTL, HAK, HLW …',
        };
    }

    /**
     * Valid Matura text types for this school type.
     *
     * @return list<string>
     */
    public function textTypes(): array
    {
        return config('lingolens.text_types.'.$this->value, []);
    }

    public function defaultTextType(): string
    {
        return $this->textTypes()[0] ?? 'Essay';
    }

    /**
     * Label for the school year, e.g. "5. Jahrgang" (BHS) or "8. Klasse" (AHS).
     */
    public function gradeLabel(int $grade): string
    {
        return $grade.'. '.($this === self::BHS ? 'Jahrgang' : 'Klasse');
    }
}
