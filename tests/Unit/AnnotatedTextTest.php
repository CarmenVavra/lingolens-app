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

    public function test_it_finds_passages_across_handwriting_line_breaks(): void
    {
        $text = "students must learn how to use\ntechnology responsible. Größe   matters";

        $this->assertSame(
            [[27, 53], [55, 70]],
            AnnotatedText::locate($text, ['use technology responsible', 'Größe matters'])
        );
    }

    public function test_straight_and_typographic_quotes_match(): void
    {
        $text = "Let\u{2019}s give it a try and \u{201E}test\u{201C} it.";

        $this->assertSame([[0, 5], [24, 30]], AnnotatedText::locate($text, ["Let's", '"test"']));
    }

    public function test_line_break_matching_never_spans_paragraphs(): void
    {
        $this->assertSame([null], AnnotatedText::locate("the end\n\nof story", ['end of']));
    }

    public function test_marks_across_line_breaks_are_rendered_on_one_line(): void
    {
        $text = "how to use\ntechnology responsible. Done.";
        $correction = new Correction(['wrong_text' => 'use technology responsible', 'error_type' => ErrorType::Grammar]);
        $correction->id = 3;
        [[$correction->start_offset, $correction->end_offset]] = AnnotatedText::locate($text, ['use technology responsible']);

        $segments = AnnotatedText::paragraphs($text, collect([$correction]))[0]['segments'];

        $this->assertSame('how to ', $segments[0]['text']);
        $this->assertSame('use technology responsible', $segments[1]['text']);
        $this->assertSame('. Done.', $segments[2]['text']);
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
