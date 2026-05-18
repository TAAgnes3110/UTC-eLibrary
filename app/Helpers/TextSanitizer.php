<?php

namespace App\Helpers;

/**
 * Làm sạch nội dung dán từ Word / trình soạn thảo (ký tự ẩn, UTF-8 lỗi).
 */
final class TextSanitizer
{
    /** Giới hạn mặc định cho mô tả sách / đồ án (longText). */
    public const DEFAULT_LONG_TEXT_MAX_CHARS = 500_000;

    public static function fromRichPaste(?string $value, int $maxChars = self::DEFAULT_LONG_TEXT_MAX_CHARS): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = str_replace(["\r\n", "\r"], "\n", $value);
        $text = str_replace("\0", '', $text);

        if ($text === '') {
            return null;
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $text = preg_replace('/\n{4,}/', "\n\n\n", $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > $maxChars) {
            $text = mb_substr($text, 0, $maxChars);
        }

        return $text;
    }
}
