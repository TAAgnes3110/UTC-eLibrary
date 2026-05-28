<?php

namespace App\Services;

use App\Enums\UploadDirectory;
use App\Helpers\FileHelpers;
use App\Models\Book;
use App\Models\DigitalAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** Chuyển preview.pdf → PNG từng trang (hiển thị web, không stream PDF). */
class DigitalAssetPreviewDisplayService
{
    /**
     * @return array{pages: list<array{page: int, path: string}>}|null
     */
    public function buildFromPreviewPdf(DigitalAsset $asset, string $previewPdfAbsolute, int $pageCount): ?array
    {
        $pageCount = max(1, $pageCount);

        if (config('deploy.allow_imagick_pdf', true) && class_exists('Imagick')) {
            $built = $this->buildPageImagesViaImagick($asset, $previewPdfAbsolute, $pageCount);
            if ($built !== null) {
                return $built;
            }
        }

        if (config('deploy.allow_shell_pdf_tools', true)) {
            $built = $this->buildPageImagesViaPdftoppm($asset, $previewPdfAbsolute, $pageCount);
            if ($built !== null) {
                return $built;
            }
        }

        return null;
    }

    public function hasPreviewDisplay(DigitalAsset $asset): bool
    {
        $pages = $asset->preview_display['pages'] ?? null;

        return is_array($pages) && $pages !== [];
    }

    /** Tạo PNG từ preview.pdf đã có (asset cũ / deploy). */
    public function ensureDisplayFromStoredPreview(DigitalAsset $asset): bool
    {
        if ($this->hasPreviewDisplay($asset)) {
            return true;
        }

        $previewPath = (string) ($asset->preview_path ?? '');
        if ($previewPath === '') {
            return false;
        }

        $diskName = (string) ($asset->storage_disk ?: FileHelpers::digitalAssetsDisk());
        if (! Storage::disk($diskName)->exists($previewPath)) {
            return false;
        }

        try {
            [$absolute, $cleanup] = FileHelpers::materializeStoragePathToLocalTemp($diskName, $previewPath);
            $pageCount = max(1, (int) ($asset->preview_page_count ?? 5));
            $display = $this->buildFromPreviewPdf($asset, $absolute, $pageCount);
            if ($cleanup) {
                @unlink($absolute);
            }
            if ($display === null) {
                return false;
            }

            $asset->forceFill(['preview_display' => $display])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('digital_asset.preview_display_build_failed', [
                'digital_asset_id' => $asset->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteDisplayFiles(DigitalAsset $asset): void
    {
        $display = $asset->preview_display;
        if (! is_array($display)) {
            return;
        }

        $disk = Storage::disk((string) ($asset->storage_disk ?: FileHelpers::digitalAssetsDisk()));
        foreach ($display['pages'] ?? [] as $page) {
            $path = (string) ($page['path'] ?? '');
            if ($path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    /**
     * @return array{book: array{id: int, title: string|null}, asset: array{id: int, original_name: string|null}, pages: list<array{page: int, image_url: string}>, back_url: string}
     */
    public function readerPreviewPayload(Book $book, DigitalAsset $asset, string $backUrl): array
    {
        $pages = [];
        foreach ($asset->preview_display['pages'] ?? [] as $row) {
            $pageNo = (int) ($row['page'] ?? 0);
            if ($pageNo < 1 || ! filled($row['path'] ?? null)) {
                continue;
            }

            $pages[] = [
                'page' => $pageNo,
                'image_url' => url(route('reader.catalog.digital-preview-page-image', [
                    'book' => $book->id,
                    'digital_asset' => $asset->id,
                    'page' => $pageNo,
                ], false)),
            ];
        }

        return [
            'book' => ['id' => (int) $book->id, 'title' => $book->title],
            'asset' => ['id' => (int) $asset->id, 'original_name' => $asset->original_name],
            'pages' => $pages,
            'back_url' => $backUrl,
        ];
    }

    public function streamPreviewPageImage(Book $book, DigitalAsset $asset, int $page): StreamedResponse
    {
        if ((int) $asset->book_id !== (int) $book->id) {
            abort(404);
        }

        if ($page < 1) {
            abort(404);
        }

        // Chỉ phục vụ trang có trong preview_display — không đoán path trên disk (tránh lộ trang ngoài paywall).
        $relative = $this->resolvePageImagePath($asset, $page);
        if ($relative === null) {
            abort(404);
        }

        $diskName = (string) ($asset->storage_disk ?: FileHelpers::digitalAssetsDisk());
        try {
            if (! Storage::disk($diskName)->exists($relative)) {
                Log::warning('digital_asset.preview_page_image_missing', [
                    'digital_asset_id' => $asset->id,
                    'book_id' => $book->id,
                    'page' => $page,
                    'disk' => $diskName,
                    'path' => $relative,
                ]);
                abort(404);
            }
        } catch (Throwable $e) {
            Log::warning('digital_asset.preview_page_image_storage_error', [
                'digital_asset_id' => $asset->id,
                'book_id' => $book->id,
                'page' => $page,
                'disk' => $diskName,
                'path' => $relative,
                'message' => $e->getMessage(),
            ]);
            abort(404);
        }

        return response()->stream(
            function () use ($diskName, $relative): void {
                $stream = Storage::disk($diskName)->readStream($relative);
                if ($stream === false) {
                    throw new \RuntimeException('Cannot read preview image.');
                }
                try {
                    fpassthru($stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'public, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function resolvePageImagePath(DigitalAsset $asset, int $page): ?string
    {
        foreach ($asset->preview_display['pages'] ?? [] as $row) {
            if ((int) ($row['page'] ?? 0) === $page) {
                $path = (string) ($row['path'] ?? '');

                return $path !== '' ? $path : null;
            }
        }

        return null;
    }

    /**
     * @return array{pages: list<array{page: int, path: string}>}|null
     */
    private function buildPageImagesViaImagick(DigitalAsset $asset, string $previewPdfAbsolute, int $pageCount): ?array
    {
        $diskName = (string) ($asset->storage_disk ?: FileHelpers::digitalAssetsDisk());
        $imagickClass = '\\Imagick';
        $pages = [];

        try {
            for ($i = 0; $i < $pageCount; $i++) {
                /** @var object $im */
                $im = new $imagickClass;
                $im->setResolution(144, 144);
                $im->readImage($previewPdfAbsolute.'['.$i.']');
                $im->setImageFormat('png');
                $im->setImageBackgroundColor('white');

                $relative = UploadDirectory::digitalAssetPreviewPageImage((int) $asset->book_id, (int) $asset->id, $i + 1);
                Storage::disk($diskName)->put($relative, $im->getImageBlob());
                $this->ensureStorageFileWorldReadable($diskName, $relative);
                $im->clear();
                $im->destroy();

                $pages[] = ['page' => $i + 1, 'path' => $relative];
            }
        } catch (Throwable $e) {
            Log::debug('digital_asset.preview_display_imagick_failed', ['message' => $e->getMessage()]);

            return null;
        }

        return ['pages' => $pages];
    }

    /**
     * @return array{pages: list<array{page: int, path: string}>}|null
     */
    private function buildPageImagesViaPdftoppm(DigitalAsset $asset, string $previewPdfAbsolute, int $pageCount): ?array
    {
        $binary = $this->resolvePdftoppmBinary();
        if ($binary === null) {
            return null;
        }

        $diskName = (string) ($asset->storage_disk ?: FileHelpers::digitalAssetsDisk());
        $tmpDir = sys_get_temp_dir().'/utc_ppm_'.uniqid('', true);
        if (! @mkdir($tmpDir) && ! is_dir($tmpDir)) {
            return null;
        }

        try {
            $prefix = $tmpDir.'/page';
            $result = Process::timeout(max(60, (int) config('services.pdf_preview.process_timeout', 180)))->run([
                $binary,
                '-png',
                '-f', '1',
                '-l', (string) $pageCount,
                $previewPdfAbsolute,
                $prefix,
            ]);

            if (! $result->successful()) {
                return null;
            }

            $pages = [];
            $files = glob($prefix.'-*.png') ?: [];
            natsort($files);
            $index = 1;
            foreach ($files as $file) {
                if ($index > $pageCount) {
                    break;
                }
                $relative = UploadDirectory::digitalAssetPreviewPageImage((int) $asset->book_id, (int) $asset->id, $index);
                Storage::disk($diskName)->put($relative, (string) file_get_contents($file));
                $this->ensureStorageFileWorldReadable($diskName, $relative);
                $pages[] = ['page' => $index, 'path' => $relative];
                $index++;
            }

            return $pages === [] ? null : ['pages' => $pages];
        } catch (Throwable $e) {
            Log::debug('digital_asset.preview_display_pdftoppm_failed', ['message' => $e->getMessage()]);

            return null;
        } finally {
            foreach (glob($tmpDir.'/*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }
    }

    private function ensureStorageFileWorldReadable(string $diskName, string $relative): void
    {
        if ($diskName !== 'local') {
            return;
        }

        try {
            $absolute = Storage::disk($diskName)->path($relative);
            if (is_file($absolute)) {
                @chmod($absolute, 0644);
            }

            // Khi artisan chạy bằng root, thư mục có thể bị tạo 0700 => php-fpm (www-data) đọc fail.
            // Mở quyền traverse/read cho cây thư mục private chứa preview ảnh.
            $storageRoot = rtrim(str_replace('\\', '/', storage_path('app/private')), '/');
            $dir = dirname($absolute);
            while ($dir !== '' && is_dir($dir)) {
                $normalized = str_replace('\\', '/', $dir);
                if (! str_starts_with($normalized, $storageRoot)) {
                    break;
                }
                @chmod($dir, 0755);
                if ($normalized === $storageRoot) {
                    break;
                }
                $parent = dirname($dir);
                if ($parent === $dir) {
                    break;
                }
                $dir = $parent;
            }
        } catch (Throwable) {
            //
        }
    }

    private function resolvePdftoppmBinary(): ?string
    {
        $configured = (string) config('services.pdf_preview.pdftoppm_binary', '');
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        foreach (['pdftoppm', 'pdftoppm.exe'] as $name) {
            $result = Process::timeout(10)->run([$finder, $name]);
            if ($result->successful()) {
                foreach (preg_split('/\R/', trim($result->output())) ?: [] as $line) {
                    $line = trim($line);
                    if ($line !== '' && is_file($line)) {
                        return $line;
                    }
                }
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $localAppData = getenv('LOCALAPPDATA') ?: '';
            if ($localAppData !== '') {
                $pattern = $localAppData.DIRECTORY_SEPARATOR
                    .'Microsoft'.DIRECTORY_SEPARATOR.'WinGet'.DIRECTORY_SEPARATOR.'Packages'
                    .DIRECTORY_SEPARATOR.'oschwartz10612.Poppler_*'
                    .DIRECTORY_SEPARATOR.'poppler-*'.DIRECTORY_SEPARATOR.'Library'
                    .DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pdftoppm.exe';
                foreach (glob($pattern) ?: [] as $candidate) {
                    if (is_file($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }
}
