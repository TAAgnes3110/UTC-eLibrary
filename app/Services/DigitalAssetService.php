<?php

namespace App\Services;

use App\Enums\UploadDirectory;
use App\Helpers\FileHelpers;
use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalAssetService
{
    public function __construct(
        private readonly DigitalAssetPreviewService $previewService
    ) {}

    /**
     * @param  array{storage_disk?: string, is_primary?: bool, visibility?: string, embargo_until?: string|null}  $attrs
     */
    public function store(Book $book, UploadedFile $file, array $attrs = []): DigitalAsset
    {
        // PDF tài liệu số nên lưu ở disk private để tránh lộ link trực tiếp.
        $disk = $attrs['storage_disk'] ?? FileHelpers::digitalAssetsDisk();
        $dir = UploadDirectory::digitalAssetsByBookId((int) $book->id);

        return DB::transaction(function () use ($book, $file, $attrs, $disk, $dir) {
            $path = FileHelpers::storeUploadedFile($file, $disk, $dir);
            $checksum = FileHelpers::hashSha256FromStorage($disk, $path);

            $maxVersion = (int) DigitalAsset::query()->where('book_id', $book->id)->max('version');
            $hasAny = DigitalAsset::query()->where('book_id', $book->id)->exists();
            $isPrimary = array_key_exists('is_primary', $attrs)
                ? (bool) $attrs['is_primary']
                : ! $hasAny;

            if ($isPrimary) {
                DigitalAsset::query()->where('book_id', $book->id)->update(['is_primary' => false]);
            }

            $asset = DigitalAsset::create([
                'book_id' => $book->id,
                'version' => $maxVersion + 1,
                'is_primary' => $isPrimary,
                'storage_disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'byte_size' => $file->getSize(),
                'checksum_sha256' => $checksum,
                'visibility' => $attrs['visibility'] ?? 'internal',
                'embargo_until' => $attrs['embargo_until'] ?? null,
            ]);

            $this->schedulePostUploadProcessing($asset, $book, $disk, $path, $asset->mime);

            return $asset;
        });
    }

    /**
     * Ảnh bìa + PDF xem trước chạy sau khi trả HTTP — tránh chặn upload/duyệt (FPDI/Imagick + R2).
     */
    public function schedulePostUploadProcessing(
        DigitalAsset $asset,
        Book $book,
        string $disk,
        string $path,
        ?string $mime = null
    ): void {
        if (! config('deploy.run_post_upload_processing_on_host', true)) {
            return;
        }

        $assetId = (int) $asset->id;
        $bookId = (int) $book->id;

        dispatch(function () use ($assetId, $bookId, $disk, $path, $mime): void {
            $asset = DigitalAsset::query()->find($assetId);
            $book = Book::query()->find($bookId);
            if (! $asset || ! $book) {
                return;
            }

            $this->trySetBookCoverFromPdf($book, $disk, $path, $mime);
            $this->previewService->generate($asset->fresh());
        })->afterResponse();
    }

    public function trySetBookCoverFromPdf(Book $book, string $disk, string $pdfPath, ?string $mime = null): void
    {
        if (! empty($book->cover_image)) {
            return;
        }

        $normalizedMime = strtolower(trim((string) $mime));
        if ($normalizedMime !== '' && $normalizedMime !== 'application/pdf') {
            return;
        }

        if (! config('deploy.allow_imagick_pdf', true) || ! class_exists('Imagick')) {
            return;
        }

        if (! Storage::disk($disk)->exists($pdfPath)) {
            return;
        }

        $absolutePdfPath = Storage::disk($disk)->path($pdfPath);
        $imagickClass = '\\Imagick';
        /** @var object $imagick */
        $imagick = new $imagickClass;
        try {
            $imagick->setResolution(120, 120);
            $imagick->readImage($absolutePdfPath.'[0]');
            $imagick->setImageFormat('png');
            $imagick->thumbnailImage(600, 0, true, true);

            $coverDir = UploadDirectory::bookPdfCovers();
            $coverPath = $coverDir.'/'.($book->book_code ?: (string) $book->id).'.png';
            Storage::disk($disk)->put($coverPath, $imagick->getImageBlob());

            $book->cover_image = $coverPath;
            $book->save();
        } catch (\Throwable) {
            // Bỏ qua nếu môi trường chưa có đủ thư viện render PDF thumbnail.
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }
    }

    public function destroy(Book $book, DigitalAsset $asset): void
    {
        if ($asset->book_id !== $book->id) {
            abort(404);
        }
        $asset->delete();
    }

    public function incrementViewCount(DigitalAsset $asset): void
    {
        DigitalAsset::query()->whereKey((int) $asset->id)->increment('view_count');
    }

    public function incrementDownloadCount(DigitalAsset $asset): void
    {
        DigitalAsset::query()->whereKey((int) $asset->id)->increment('download_count');
    }

    /**
     * Tổng lượt xem mọi file đính kèm (cùng công thức danh mục tra cứu).
     */
    public function sumViewCountForBook(int $bookId): int
    {
        return (int) DigitalAsset::query()
            ->where('book_id', $bookId)
            ->sum('view_count');
    }

    /**
     * Thống kê đọc/tải theo đầu mục sách (cộng mọi file đính kèm).
     *
     * @return array{view_count: int, download_count: int}
     */
    public function aggregatedReaderStatsForBook(Book $book): array
    {
        $row = DigitalAsset::query()
            ->where('book_id', (int) $book->id)
            ->selectRaw('COALESCE(SUM(view_count), 0) as view_count, COALESCE(SUM(download_count), 0) as download_count')
            ->first();

        return [
            'view_count' => (int) ($row->view_count ?? 0),
            'download_count' => (int) ($row->download_count ?? 0),
        ];
    }

    public function resolvePrimaryAsset(Book $book): ?DigitalAsset
    {
        $assets = $book->relationLoaded('digitalAssets')
            ? $book->digitalAssets
            : $book->digitalAssets()->get();

        if ($assets->isEmpty()) {
            return null;
        }

        return $assets->sortByDesc(fn (DigitalAsset $it) => (int) ($it->is_primary ?? false))->first();
    }

    public function buildPdfDownloadFilename(DigitalAsset $digital_asset, ?Book $book = null): string
    {
        $title = (string) ($digital_asset->original_name ?: $book?->title ?: 'tai-lieu');
        $safeFilename = preg_replace('/[^\pL\pN\s\-_.()]+/u', '_', $title) ?: 'tai-lieu';
        if (! str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }

        return $safeFilename;
    }

    /**
     * Stream PDF gốc kèm Content-Length (tránh client nhận file 0 KB / cắt cụt).
     */
    public function streamPdfDownloadResponse(DigitalAsset $digital_asset, string $safeFilename): StreamedResponse
    {
        $disk = (string) ($digital_asset->storage_disk ?: config('filesystems.digital_assets_disk', 'local'));
        $path = (string) $digital_asset->path;
        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            abort(404, __('Không tìm thấy file PDF.'));
        }

        $byteSize = Storage::disk($disk)->size($path);
        if ($byteSize === false || $byteSize < 1) {
            abort(404, __('File PDF trống hoặc không đọc được từ kho lưu trữ.'));
        }

        return response()->streamDownload(
            function () use ($disk, $path): void {
                $stream = Storage::disk($disk)->readStream($path);
                if ($stream === false) {
                    throw new \RuntimeException('Cannot read PDF stream.');
                }
                try {
                    fpassthru($stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            $safeFilename,
            [
                'Content-Type' => 'application/pdf',
                'Content-Length' => (string) $byteSize,
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
