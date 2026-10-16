<?php

namespace Tests\Unit;

use App\Enums\ErrorType;
use App\Services\Correction\OpenAiTextCorrector;
use PHPUnit\Framework\TestCase;

class CorrectionNormalizationTest extends TestCase
{
    public function test_ai_error_types_are_mapped_onto_the_enum(): void
    {
        $this->assertSame(ErrorType::TechnicalTerm, ErrorType::fromAi('technical term'));
        $this->assertSame(ErrorType::TechnicalTerm, ErrorType::fromAi('Technical-Term'));
        $this->assertSame(ErrorType::Grammar, ErrorType::fromAi('Grammatik'));
        $this->assertSame(ErrorType::Style, ErrorType::fromAi('Spelling'));
    }

    public function test_invalid_corrections_are_dropped(): void
    {
        $result = OpenAiTextCorrector::normalize([
            'summary' => ' Gut. ',
            'corrections' => [
                ['original' => 'gonna', 'corrected' => 'going to', 'type' => 'Style', 'explanation' => 'Formell.'],
                ['original' => '', 'corrected' => 'x'],
                ['original' => str_repeat('a', 300), 'corrected' => 'b'],
                'kaputt',
            ],
        ]);

        $this->assertSame('Gut.', $result['summary']);
        $this->assertSame('', $result['matura_tipp']);
        $this->assertCount(1, $result['corrections']);
        $this->assertSame('gonna', $result['corrections'][0]['original']);
    }
}
