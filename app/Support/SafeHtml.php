<?php

namespace App\Support;

/**
 * Lọc HTML lưu DB — giảm XSS khi render v-html (Quill tin tức).
 */
final class SafeHtml
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><h6>'
        .'<ul><ol><li><a><img><blockquote><span><div><table><thead><tbody><tr><td><th><hr><sub><sup>';

    public static function sanitize(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html) ?? $html;
        $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html) ?? $html;
        $html = preg_replace('/<embed\b[^>]*\/?>/is', '', $html) ?? $html;
        $html = preg_replace('/\s(on\w+|style|formaction)\s*=\s*("|\').*?\2/i', '', $html) ?? $html;
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;
        $html = preg_replace('/data\s*:\s*text\/html/i', '', $html) ?? $html;

        return trim(strip_tags($html, self::ALLOWED_TAGS));
    }
}
