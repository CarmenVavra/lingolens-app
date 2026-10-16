<?php

namespace Tests\Unit;

use App\Support\TextNormalizer;
use PHPUnit\Framework\TestCase;

class TextNormalizerTest extends TestCase
{
    public function test_it_cleans_whitespace_and_line_breaks(): void
    {
        $raw = "\u{FEFF}  Title\t\t here \r\n\r\n\r\n\r\nFirst   line \nsecond line\u{00A0}end  ";

        $this->assertSame("Title here\n\nFirst line\nsecond line end", TextNormalizer::normalize($raw));
    }

    public function test_it_converts_windows_1252_to_utf8(): void
    {
        $this->assertSame('naïve café', TextNormalizer::normalize(mb_convert_encoding('naïve café', 'Windows-1252', 'UTF-8')));
    }

    public function test_it_counts_letters_only(): void
    {
        $this->assertSame(5, TextNormalizer::letterCount("Größe 123 !?\n"));
    }
}
