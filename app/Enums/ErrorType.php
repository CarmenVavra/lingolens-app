<?php

namespace App\Enums;

/**
 * Error categories returned by the AI correction (corrections.error_type).
 * Colours follow the design handoff: base (borders/bars), tint (fills), deep (text).
 */
enum ErrorType: string
{
    case Grammar = 'Grammar';
    case Vocabulary = 'Vocabulary';
    case Style = 'Style';
    case TechnicalTerm = 'Technical Term';

    /**
     * German label shown in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Grammar => 'Grammatik',
            self::Vocabulary => 'Vokabular',
            self::Style => 'Stil',
            self::TechnicalTerm => 'Fachbegriff',
        };
    }

    /**
     * Label used in sentences like "Größter Hebel: Fachbegriffe".
     */
    public function pluralLabel(): string
    {
        return $this === self::TechnicalTerm ? 'Fachbegriffe' : $this->label();
    }

    /**
     * CSS modifier used for the colour scheme (e.g. "grammar" → .is-grammar).
     */
    public function slug(): string
    {
        return match ($this) {
            self::Grammar => 'grammar',
            self::Vocabulary => 'vocabulary',
            self::Style => 'style',
            self::TechnicalTerm => 'technical',
        };
    }

    public function base(): string
    {
        return match ($this) {
            self::Grammar => '#E0412F',
            self::Vocabulary => '#2D7DD2',
            self::Style => '#F2B233',
            self::TechnicalTerm => '#7E4FC9',
        };
    }

    public function tint(): string
    {
        return match ($this) {
            self::Grammar => '#FAD4CC',
            self::Vocabulary => '#D3E5F8',
            self::Style => '#FCEBB8',
            self::TechnicalTerm => '#E6DAF7',
        };
    }

    public function deep(): string
    {
        return match ($this) {
            self::Grammar => '#9E2416',
            self::Vocabulary => '#164E8C',
            self::Style => '#7A5200',
            self::TechnicalTerm => '#4D2A88',
        };
    }

    /**
     * Map a loosely formatted AI value ("technical term", "grammar") onto a case.
     * Unknown values fall back to Style so no correction gets lost.
     */
    public static function fromAi(?string $value): self
    {
        $normalized = strtolower(trim((string) $value));

        foreach (self::cases() as $case) {
            if (strtolower($case->value) === $normalized || strtolower($case->label()) === $normalized) {
                return $case;
            }
        }

        return str_contains($normalized, 'techn') ? self::TechnicalTerm : self::Style;
    }

    /**
     * Colour/label data for the frontend (Alpine.js).
     *
     * @return array<string, array<string, string>>
     */
    public static function palette(): array
    {
        $palette = [];

        foreach (self::cases() as $case) {
            $palette[$case->value] = [
                'label' => $case->label(),
                'slug' => $case->slug(),
                'base' => $case->base(),
                'tint' => $case->tint(),
                'deep' => $case->deep(),
            ];
        }

        return $palette;
    }
}
