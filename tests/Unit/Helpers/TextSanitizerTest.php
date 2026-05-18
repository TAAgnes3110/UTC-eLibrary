<?php

namespace Tests\Unit\Helpers;

use App\Helpers\TextSanitizer;
use Tests\TestCase;

class TextSanitizerTest extends TestCase
{
    public function test_strips_null_bytes_and_normalizes_line_endings(): void
    {
        $input = "Dòng 1\r\nDòng 2\0ẩn";

        $this->assertSame("Dòng 1\nDòng 2ẩn", TextSanitizer::fromRichPaste($input));
    }

    public function test_truncates_beyond_max_chars(): void
    {
        $input = str_repeat('á', 20);

        $this->assertSame(10, mb_strlen(TextSanitizer::fromRichPaste($input, 10) ?? ''));
    }
}
