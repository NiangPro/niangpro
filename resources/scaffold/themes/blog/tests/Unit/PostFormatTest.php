<?php

namespace Tests\Unit;

use App\Support\PostFormat;
use PHPUnit\Framework\TestCase;

class PostFormatTest extends TestCase
{
    public function test_dates_are_written_in_french(): void
    {
        $this->assertSame('14 septembre 2026', PostFormat::date('2026-09-14 09:00:00'));
        $this->assertSame('1er février 2027', PostFormat::date('2027-02-01'));
        $this->assertSame('', PostFormat::date('pas une date'));
    }

    public function test_reading_time_is_at_least_one_minute_and_rounds_up(): void
    {
        $this->assertSame(1, PostFormat::readingMinutes(''));
        $this->assertSame(1, PostFormat::readingMinutes('quelques mots'));
        $this->assertSame(1, PostFormat::readingMinutes(str_repeat('mot ', 200)));
        $this->assertSame(2, PostFormat::readingMinutes(str_repeat('mot ', 201)));
    }

    public function test_reading_time_counts_accented_words(): void
    {
        $this->assertSame(2, PostFormat::readingMinutes(str_repeat('élève écrivain ', 150)));
    }

    public function test_the_body_is_turned_into_headings_paragraphs_quotes_and_lists(): void
    {
        $html = PostFormat::html("## Titre\n\nUn paragraphe.\n\n> Une citation\n\n- un\n- deux");

        $this->assertStringContainsString('<h2>Titre</h2>', $html);
        $this->assertStringContainsString('<p>Un paragraphe.</p>', $html);
        $this->assertStringContainsString('<blockquote><p>Une citation</p></blockquote>', $html);
        $this->assertStringContainsString('<ul><li>un</li><li>deux</li></ul>', $html);
    }

    public function test_html_in_the_body_is_escaped_never_interpreted(): void
    {
        $html = PostFormat::html("## <script>alert(1)</script>\n\n<img src=x onerror=alert(1)>\n\n- <b>gras</b>");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_a_single_line_break_inside_a_paragraph_is_kept(): void
    {
        $this->assertStringContainsString("<p>ligne un<br />\nligne deux</p>", PostFormat::html("ligne un\nligne deux"));
    }
}
