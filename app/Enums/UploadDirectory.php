<?php

namespace App\Enums;

/**
 * Đường dẫn lưu file trên disk media — không dùng backed enum vì cần factory theo id bảng.
 */
final class UploadDirectory
{
    public const BASE = 'utc-elibrary';

    public static function userAvatars(): string
    {
        return self::BASE.'/users/avatars';
    }

    public static function bookCovers(?string $resourceType = null): string
    {
        $normalized = strtolower(trim((string) $resourceType));
        $kind = $normalized === 'digital' ? 'digital' : 'physical';

        return self::BASE."/books/covers/{$kind}";
    }

    public static function libraryCardPhotos(): string
    {
        return self::BASE.'/library-cards/photos';
    }

    public static function newsThumbnails(): string
    {
        return self::BASE.'/news/thumbnails';
    }

    public static function newsContentImages(): string
    {
        return self::BASE.'/news/content-images';
    }

    public static function newsAttachments(): string
    {
        return self::BASE.'/news/attachments';
    }

    public static function digitalSubmissionFiles(): string
    {
        return self::BASE.'/digital-submissions/files';
    }

    public static function digitalSubmissionCovers(): string
    {
        return self::BASE.'/digital-submissions/covers';
    }

    public static function digitalAssetsByBookId(int $bookId): string
    {
        return self::BASE.'/books/digital-assets/'.max(0, $bookId);
    }

    public static function digitalAssetPreview(int $bookId, int $assetId): string
    {
        return self::digitalAssetsByBookId($bookId).'/'.max(0, $assetId).'/preview.pdf';
    }

    public static function digitalAssetPreviewPageImage(int $bookId, int $assetId, int $page): string
    {
        return self::digitalAssetsByBookId($bookId).'/'.max(0, $assetId).'/preview-pages/'.max(1, $page).'.png';
    }

    public static function bookPdfCovers(): string
    {
        return self::BASE.'/books/pdf-covers';
    }

    public static function forTable(string $table): string
    {
        $key = strtolower(trim($table, '/'));

        return match ($key) {
            'users' => self::userAvatars(),
            'books' => self::bookCovers(),
            'library_cards' => self::libraryCardPhotos(),
            'news_posts' => self::newsThumbnails(),
            'digital-assets' => self::BASE.'/books/digital-assets',
            'digital-document-submissions' => self::digitalSubmissionFiles(),
            default => self::BASE.'/misc/'.$key,
        };
    }
}
