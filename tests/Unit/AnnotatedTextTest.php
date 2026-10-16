<?php

namespace Tests\Unit;

use App\Enums\ErrorType;
use App\Models\Correction;
use App\Support\AnnotatedText;
use PHPUnit\Framework\TestCase;

class AnnotatedTextTest extends TestCase
{
    public function test_it_locates_passages_sequentially(): void
    {
        $text = 'a lot of water and a lot of heat';

        $this->assertSame([[0, 8], [19, 27]], AnnotatedText::locate($text, ['a lot of', 'a lot of']));
    }

    public function test_it_falls_back_to_case_insensitive_matches_and_reports_missing_ones(): void
    {
        $this->assertSame([[0, 5], null], AnnotatedText::locate('Since 20 minutes', ['since', 'nowhere']));
    }

    public function test_it_uses_character_offsets_for_multibyte_text(): void
    {
        $this->assertSame([[6, 11]], AnnotatedText::locate('Größe stuff', ['stuff']));
    }

    public function test_it_builds_headings_paragraphs_and_marks(): void
    {
        $text = "1  Method\n\nWe heated it since 20 minutes.\nThen we stopped.";
        $correction = new Correction(['wrong_text' => 'since 20 minutes', 'error_type' => ErrorType::Grammar]);
        $correction->id = 7;
        $correction->start_offset = 24;
        $correction->end_offset = 40;

        $paragraphs = AnnotatedText::paragraphs($text, collect([$correction]));

        $this->assertCount(2, $paragraphs);
        $this->assertTrue($paragraphs[0]['heading']);
        $this->assertSame('1  Method', $paragraphs[0]['segments'][0]['text']);

        $segments = $paragraphs[1]['segments'];
        $this->assertFalse($paragraphs[1]['heading']);
        $this->assertSame('We heated it ', $segments[0]['text']);
        $this->assertSame($correction, $segments[1]['correction']);
        $this->assertSame('since 20 minutes', $segments[1]['text']);
        $this->assertSame('. Then we stopped.', $segments[2]['text']);
    }
}
