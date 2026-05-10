<?php

namespace App\Services;

use App\Enums\UploadDirectory;
use App\Helpers\FileHelpers;
use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DigitalAssetService
{
    /**
     * @param  array{storage_disk?: string, is_primary?: bool, visibility?: string, embargo_until?: string|null}  $attrs
     */
    public function store(Book $book, UploadedFile $file, array $attrs = []): DigitalAsset
    {
        $disk = $attrs['storage_disk'] ?? (string) config('filesystems.media_disk', 'public');
        $dir = UploadDirectory::digitalAssetsByBookId((int) $book->id);

        return DB::transaction(function () use ($book, $file, $attrs, $disk, $dir) {
            $path = FileHelpers::storeUploadedFile($file, $disk, $dir);
            $absolute = Storage::disk($disk)->path($path);
            $checksum = is_readable($absolute) ? hash_file('sha256', $absolute) : null;

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

            $this->trySetBookCoverFromPdf($book, $disk, $path, $asset->mime);

            return $asset;
        });
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

        if (! class_exists(\Imagick::class)) {
            return;
        }

        if (! Storage::disk($disk)->exists($pdfPath)) {
            return;
        }

        $absolutePdfPath = Storage::disk($disk)->path($pdfPath);
        $imagick = new \Imagick;
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
}
