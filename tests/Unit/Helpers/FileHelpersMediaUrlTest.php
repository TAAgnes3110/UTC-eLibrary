<?php

namespace Tests\Unit\Helpers;

use App\Helpers\FileHelpers;
use Tests\TestCase;

class FileHelpersMediaUrlTest extends TestCase
{
    public function test_root_relative_media_url_for_public_disk(): void
    {
        config([
            'filesystems.media_disk' => 'public',
            'filesystems.disks.public.driver' => 'local',
        ]);

        $this->assertSame(
            '/upload/news/attachments/sample.pdf',
            FileHelpers::rootRelativeMediaUrl('upload/news/attachments/sample.pdf')
        );
    }

    public function test_rewrite_absolute_media_urls_in_html(): void
    {
        $html = '<p><a href="http://localhost:8000/upload/news/attachments/sample.pdf">Tệp PDF</a></p>';

        $rewritten = FileHelpers::rewriteAbsoluteMediaUrlsInHtml($html);

        $this->assertSame(
            '<p><a href="/upload/news/attachments/sample.pdf">Tệp PDF</a></p>',
            $rewritten
        );
    }
}
