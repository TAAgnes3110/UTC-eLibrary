<?php

namespace Tests\Unit\Support;

use App\Support\SafeHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SafeHtmlTest extends TestCase
{
    #[Test]
    #[DataProvider('xssPayloadProvider')]
    public function sanitize_strips_dangerous_markup(string $input, string $mustNotContain): void
    {
        $result = SafeHtml::sanitize($input);

        $this->assertStringNotContainsString($mustNotContain, strtolower($result));
    }

    public static function xssPayloadProvider(): array
    {
        return [
            'script tag' => ['<p>Hi</p><script>alert(1)</script>', 'script'],
            'iframe' => ['<iframe src="https://evil.test"></iframe><p>ok</p>', 'iframe'],
            'onerror attr' => ['<img src=x onerror="alert(1)">', 'onerror'],
            'javascript url' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
        ];
    }

    #[Test]
    public function sanitize_keeps_safe_formatting(): void
    {
        $html = '<p><strong>UTC</strong> thư viện</p>';

        $this->assertSame($html, SafeHtml::sanitize($html));
    }
}
