<?php

namespace App\Services;

use App\Enums\UploadDirectory;
use App\Helpers\FileHelpers;
use App\Models\DigitalAsset;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Tạo file PDF xem trước (N trang đầu).
 * Thứ tự: FPDI (PDF đơn giản) → qpdf / Ghostscript (PDF nén hiện đại) → Imagick (nếu có).
 */
class DigitalAssetPreviewService
{
    public const MAX_PREVIEW_PAGES_CAP = 50;

    public function __construct(
        private readonly DigitalPaywallService $paywall,
        private readonly DigitalAssetPreviewDisplayService $display,
    ) {}

    public function previewRelativePath(DigitalAsset $asset): string
    {
        return UploadDirectory::digitalAssetPreview((int) $asset->book_id, (int) $asset->id);
    }

    public function hasPreview(DigitalAsset $asset): bool
    {
        $path = (string) ($asset->preview_path ?? '');
        if ($path === '') {
            return false;
        }

        $disk = (string) ($asset->storage_disk ?: config('filesystems.digital_assets_disk', 'local'));

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Tạo hoặc làm mới preview.pdf. Trả false nếu tắt preview hoặc lỗi (không chặn upload).
     */
    public function generate(DigitalAsset $asset, ?int $maxPages = null): bool
    {
        $asset->loadMissing('paywallSetting');
        $limit = $maxPages ?? $this->paywall->resolvePreviewMaxPages($asset);
        if ($limit <= 0) {
            $this->clearPreview($asset);

            return false;
        }

        $limit = min($limit, self::MAX_PREVIEW_PAGES_CAP);
        $sourcePath = (string) $asset->path;
        if ($sourcePath === '') {
            return false;
        }

        $diskName = (string) ($asset->storage_disk ?: config('filesystems.digital_assets_disk', 'local'));
        $disk = Storage::disk($diskName);
        if (! $disk->exists($sourcePath)) {
            return false;
        }

        if ($this->display->hasPreviewDisplay($asset)) {
            $this->display->deleteDisplayFiles($asset);
        }

        $previewPath = $this->previewRelativePath($asset);
        $tmpSource = null;
        $tmpPreview = null;

        try {
            [$tmpSource, $cleanupSource] = FileHelpers::materializeStoragePathToLocalTemp($diskName, $sourcePath);
            $tmpPreview = tempnam(sys_get_temp_dir(), 'utc_preview_');
            if ($tmpPreview === false) {
                return false;
            }
            $tmpPreview .= '.pdf';

            $pagesWritten = $this->extractPagesToFile($tmpSource, $tmpPreview, $limit);
            if ($pagesWritten <= 0) {
                return false;
            }

            $disk->put($previewPath, (string) file_get_contents($tmpPreview));

            $display = $this->display->buildFromPreviewPdf($asset, $tmpPreview, $pagesWritten);

            $asset->forceFill([
                'preview_path' => $previewPath,
                'preview_page_count' => $pagesWritten,
                'preview_generated_at' => now(),
                'preview_display' => $display,
            ])->save();

            return $display !== null;
        } catch (Throwable $e) {
            Log::warning('digital_asset.preview_generate_failed', [
                'digital_asset_id' => $asset->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            if ($tmpSource !== null && ($cleanupSource ?? false)) {
                @unlink($tmpSource);
            }
            if ($tmpPreview !== null && is_file($tmpPreview)) {
                @unlink($tmpPreview);
            }
        }
    }

    public function clearPreview(DigitalAsset $asset): void
    {
        $this->display->deleteDisplayFiles($asset);

        $path = (string) ($asset->preview_path ?? '');
        if ($path !== '') {
            $disk = (string) ($asset->storage_disk ?: config('filesystems.digital_assets_disk', 'local'));
            Storage::disk($disk)->delete($path);
        }

        $asset->forceFill([
            'preview_path' => null,
            'preview_page_count' => null,
            'preview_generated_at' => null,
            'preview_display' => null,
        ])->save();
    }

    public function isPreviewAvailableForReader(DigitalAsset $asset): bool
    {
        if ($this->paywall->resolvePreviewMaxPages($asset) <= 0) {
            return false;
        }

        if ($this->display->hasPreviewDisplay($asset)) {
            return true;
        }

        if ($this->hasPreview($asset)) {
            return true;
        }

        return config('deploy.allow_runtime_preview_generation', true)
            && filled($asset->path);
    }

    private function extractPagesToFile(string $sourceAbsolute, string $destAbsolute, int $maxPages): int
    {
        $strategies = [
            fn (): int => $this->extractPagesViaFpdi($sourceAbsolute, $destAbsolute, $maxPages),
        ];

        if (config('deploy.allow_shell_pdf_tools', true)) {
            $strategies[] = fn (): int => $this->extractPagesViaQpdf($sourceAbsolute, $destAbsolute, $maxPages);
            $strategies[] = fn (): int => $this->extractPagesViaGhostscript($sourceAbsolute, $destAbsolute, $maxPages);
        }

        if (config('deploy.allow_imagick_pdf', true)) {
            $strategies[] = fn (): int => $this->extractPagesViaImagick($sourceAbsolute, $destAbsolute, $maxPages);
        }

        $lastError = null;
        foreach ($strategies as $strategy) {
            try {
                $pages = $strategy();
                if ($pages > 0 && is_file($destAbsolute) && filesize($destAbsolute) > 0) {
                    return $pages;
                }
            } catch (Throwable $e) {
                $lastError = $e;
                if (is_file($destAbsolute)) {
                    @unlink($destAbsolute);
                }
            }
        }

        if ($lastError !== null) {
            throw $lastError;
        }

        return 0;
    }

    private function extractPagesViaFpdi(string $sourceAbsolute, string $destAbsolute, int $maxPages): int
    {
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($sourceAbsolute);
        $limit = min($maxPages, $pageCount);
        if ($limit <= 0) {
            return 0;
        }

        for ($pageNo = 1; $pageNo <= $limit; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplId);
            $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
        }

        $pdf->Output('F', $destAbsolute);

        return $limit;
    }

    private function extractPagesViaQpdf(string $sourceAbsolute, string $destAbsolute, int $maxPages): int
    {
        $binary = $this->resolveQpdfBinary();
        if ($binary === null) {
            return 0;
        }

        $pageCount = $this->resolveSourcePageCount($sourceAbsolute, $binary);
        $lastPage = min($maxPages, max(1, $pageCount));

        $result = Process::timeout(180)->run([
            $binary,
            '--empty',
            '--pages',
            $sourceAbsolute,
            '1-'.$lastPage,
            '--',
            $destAbsolute,
        ]);

        if (! $this->qpdfRunSucceeded($result, $destAbsolute)) {
            throw new \RuntimeException(trim($result->errorOutput() ?: $result->output() ?: 'qpdf failed'));
        }

        return $lastPage;
    }

    private function extractPagesViaGhostscript(string $sourceAbsolute, string $destAbsolute, int $maxPages): int
    {
        $binary = $this->resolveGhostscriptBinary();
        if ($binary === null) {
            return 0;
        }

        $pageCount = $this->resolveSourcePageCount($sourceAbsolute);
        $lastPage = min($maxPages, max(1, $pageCount));

        $result = Process::timeout(180)->run([
            $binary,
            '-sDEVICE=pdfwrite',
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dFirstPage=1',
            '-dLastPage='.$lastPage,
            '-sOutputFile='.$destAbsolute,
            $sourceAbsolute,
        ]);

        if (! $result->successful() || ! is_file($destAbsolute)) {
            throw new \RuntimeException(trim($result->errorOutput() ?: $result->output() ?: 'ghostscript failed'));
        }

        return $lastPage;
    }

    private function extractPagesViaImagick(string $sourceAbsolute, string $destAbsolute, int $maxPages): int
    {
        if (! class_exists('Imagick')) {
            return 0;
        }

        $pageCount = $this->resolveSourcePageCount($sourceAbsolute);
        $limit = min($maxPages, max(1, $pageCount));

        $imagickClass = '\\Imagick';
        /** @var object $imagick */
        $imagick = new $imagickClass;
        try {
            $imagick->setResolution(120, 120);
            $imagick->readImage($sourceAbsolute.'[0-'.($limit - 1).']');
            $imagick->setImageFormat('pdf');
            $imagick->writeImages($destAbsolute, true);
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }

        if (! is_file($destAbsolute) || filesize($destAbsolute) < 1) {
            throw new \RuntimeException('Imagick preview output empty');
        }

        return $limit;
    }

    private function resolveSourcePageCount(string $sourceAbsolute, ?string $qpdfBinary = null): int
    {
        $qpdfBinary ??= $this->resolveQpdfBinary();
        if ($qpdfBinary !== null) {
            $result = Process::timeout(60)->run([$qpdfBinary, '--show-npages', $sourceAbsolute]);
            if ($this->qpdfRunSucceeded($result)) {
                $count = (int) trim($result->output());
                if ($count > 0) {
                    return $count;
                }
            }
        }

        if (class_exists('Imagick')) {
            $imagickClass = '\\Imagick';
            /** @var object $imagick */
            $imagick = new $imagickClass;
            try {
                $imagick->pingImage($sourceAbsolute);
                $count = (int) $imagick->getNumberImages();

                return max(1, $count);
            } catch (Throwable) {
                //
            } finally {
                $imagick->clear();
                $imagick->destroy();
            }
        }

        try {
            $pdf = new Fpdi;
            $count = $pdf->setSourceFile($sourceAbsolute);

            return max(1, $count);
        } catch (Throwable) {
            return self::MAX_PREVIEW_PAGES_CAP;
        }
    }

    private function resolveQpdfBinary(): ?string
    {
        $configured = (string) config('services.pdf_preview.qpdf_binary', '');
        if ($configured !== '' && $this->isExecutableBinary($configured)) {
            return $configured;
        }

        $fromPath = $this->resolveCliBinary('', ['qpdf', 'qpdf.exe']);
        if ($fromPath !== null) {
            return $fromPath;
        }

        return $this->discoverQpdfInstallPath();
    }

    private function resolveGhostscriptBinary(): ?string
    {
        return $this->resolveCliBinary(
            (string) config('services.pdf_preview.ghostscript_binary', ''),
            ['gs', 'gswin64c', 'gswin32c', 'gs.exe', 'gswin64c.exe', 'gswin32c.exe']
        );
    }

    /**
     * @param  list<string>  $candidates
     */
    private function resolveCliBinary(string $configured, array $candidates): ?string
    {
        if ($configured !== '') {
            if ($this->isExecutableBinary($configured)) {
                return $configured;
            }
        }

        foreach ($candidates as $name) {
            $found = $this->findBinaryOnPath($name);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function isExecutableBinary(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return is_executable($path) || str_ends_with(strtolower($path), '.exe');
    }

    private function findBinaryOnPath(string $name): ?string
    {
        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $result = Process::timeout(10)->run([$finder, $name]);
        if (! $result->successful()) {
            return null;
        }

        foreach (preg_split('/\R/', trim($result->output())) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && is_file($line)) {
                return $line;
            }
        }

        return null;
    }

    /** qpdf trả mã 3 khi có cảnh báo nhưng vẫn xử lý xong. */
    private function qpdfRunSucceeded(ProcessResult $result, ?string $outputFile = null): bool
    {
        $code = $result->exitCode();
        if ($code !== 0 && $code !== 3) {
            return false;
        }

        if ($outputFile !== null) {
            return is_file($outputFile) && filesize($outputFile) > 0;
        }

        return true;
    }

    private function discoverQpdfInstallPath(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $roots = [
            getenv('ProgramFiles') ?: 'C:\\Program Files',
            getenv('ProgramFiles(x86)') ?: 'C:\\Program Files (x86)',
        ];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            $matches = glob($root.DIRECTORY_SEPARATOR.'qpdf*'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'qpdf.exe') ?: [];
            foreach ($matches as $candidate) {
                if ($this->isExecutableBinary($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
