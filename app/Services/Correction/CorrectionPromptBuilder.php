<?php

namespace App\Services\Correction;

use App\Enums\ErrorType;
use App\Enums\SchoolType;
use App\Models\Scan;
use Illuminate\Support\Facades\Config;

/**
 * Builds the system prompt for the AI correction (see blueprint, section 3).
 */
class CorrectionPromptBuilder
{
    public function build(Scan $scan): string
    {
        $prompt = "Du bist Englischlehrer an einer österreichischen Schule ({$scan->school_type->value})";

        if ($scan->school_type === SchoolType::BHS && $scan->department) {
            $prompt .= " für die Fachrichtung {$scan->department}";
        }

        $prompt .= ". Korrigiere den Text auf B2 Matura-Niveau für die Textsorte '{$scan->text_type}'.";

        if ($scan->department === config('lingolens.chemistry_department')) {
            $examples = collect(Config::array('lingolens.chemistry_terms'))
                ->map(fn (string $correct, string $wrong) => "'{$wrong}' → '{$correct}'")
                ->implode(', ');

            $prompt .= ' Achte strikt darauf, dass Alltagssprache durch chemische Fachbegriffe ersetzt wird'
                ." (z.B. {$examples}). Markiere dies als 'Technical Term'.";
        }

        $types = collect(ErrorType::cases())->map(fn (ErrorType $type) => "'{$type->value}'")->implode(', ');

        return $prompt
            .' Antworte didaktisch auf Deutsch als valides JSON mit den Keys:'
            ." 'title' (kurzer englischer Titel des Textes, max. 60 Zeichen),"
            ." 'summary' (2 Sätze Gesamteindruck),"
            ." 'textsorten_check' (Aufbau und Merkmale der Textsorte),"
            ." 'matura_tipp' (ein konkreter Tipp für die Matura),"
            ." 'corrections' (Array von Objekten mit 'original', 'corrected', 'type', 'explanation')."
            ." 'original' muss exakt (Zeichen für Zeichen) im Schülertext vorkommen und höchstens 200 Zeichen lang sein."
            ." 'type' ist genau einer von: {$types}."
            ." 'explanation' erklärt den Fehler kurz und verständlich auf Deutsch."
            .' Liste die Korrekturen in der Reihenfolge, in der sie im Text vorkommen.';
    }
}
