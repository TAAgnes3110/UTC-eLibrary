<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\UploadDirectory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileHelpers
{
    public const EXCEL_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public static function mimeForImageExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    /** Bỏ qua file rác thường gặp trong zip (macOS __MACOSX, ._*, file ẩn). */
    public static function shouldSkipZipExtractedFile(\SplFileInfo $fileInfo): bool
    {
        $path = str_replace('\\', '/', $fileInfo->getPathname());
        if (str_contains($path, '/__MACOSX/')) {
            return true;
        }
        $name = $fileInfo->getFilename();
        if ($name === '' || $name === '.' || $name === '..') {
            return true;
        }
        if (str_starts_with($name, '._')) {
            return true;
        }
        if (str_starts_with($name, '.')) {
            return true;
        }

        return false;
    }

    /**
     * Lưu file upload lên Storage.
     * Trả về đường dẫn tương đối (để lưu DB).
     */
    public static function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $directory,
        ?string $filename = null
    ): string {
        $directory = trim($directory, '/');
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        $name = $filename ? trim($filename) : Str::uuid()->toString();
        if ($ext !== '') {
            $name .= '.'.$ext;
        }

        return $file->storeAs($directory, $name, $disk);
    }

    public static function deleteIfExists(?string $path, string $disk = 'public'): void
    {
        if (empty($path)) {
            return;
        }

        $relativePath = self::normalizeStoragePath((string) $path, $disk);
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        if (Storage::disk($disk)->exists($relativePath)) {
            Storage::disk($disk)->delete($relativePath);
        }
    }

    public static function mediaDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public static function digitalAssetsDisk(): string
    {
        return (string) config('filesystems.digital_assets_disk', 'local');
    }

    /**
     * Đường dẫn tuyệt đối để CLI/Imagick đọc file trên disk (hoặc bản copy tạm trên cloud disk).
     *
     * @return array{0: string, 1: bool} [absolutePath, shouldUnlink]
     */
    public static function materializeStoragePathToLocalTemp(string $diskName, string $relativePath): array
    {
        $adapter = Storage::disk($diskName);
        try {
            $localPath = $adapter->path($relativePath);
            if (is_readable($localPath)) {
                return [$localPath, false];
            }
        } catch (\Throwable) {
            //
        }

        $tmp = tempnam(sys_get_temp_dir(), 'utc_mat_');
        if ($tmp === false) {
            throw new \RuntimeException('Cannot create temp file');
        }
        file_put_contents($tmp, $adapter->get($relativePath));

        return [$tmp, true];
    }

    /** Sao chép object giữa disk (local / R2 / S3) — không cần path() tuyệt đối. */
    public static function copyStorageObject(string $fromDisk, string $fromPath, string $toDisk, string $toPath): void
    {
        $fromPath = trim($fromPath, '/');
        $toPath = trim($toPath, '/');
        if ($fromPath === '' || $toPath === '') {
            return;
        }

        if ($fromDisk === $toDisk) {
            Storage::disk($toDisk)->copy($fromPath, $toPath);

            return;
        }

        $stream = Storage::disk($fromDisk)->readStream($fromPath);
        if ($stream === false) {
            throw new \RuntimeException('Cannot read storage object.');
        }

        try {
            Storage::disk($toDisk)->writeStream($toPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /** SHA-256 từ stream — dùng được với disk remote (R2/S3), không cần path() local. */
    public static function hashSha256FromStorage(string $disk, string $path): ?string
    {
        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        try {
            $stream = Storage::disk($disk)->readStream($path);
            if ($stream === false) {
                return null;
            }

            $ctx = hash_init('sha256');
            while (! feof($stream)) {
                $chunk = fread($stream, 1024 * 1024);
                if ($chunk === false) {
                    break;
                }
                hash_update($ctx, $chunk);
            }
            fclose($stream);

            return hash_final($ctx);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function mediaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with((string) $path, 'http')) {
            return (string) $path;
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(self::mediaDisk());

        return $storage->url((string) $path);
    }

    public static function mediaDefaultUrl(string $key): ?string
    {
        $value = config("media.defaults.{$key}");
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return asset(ltrim($value, '/'));
    }

    private static function normalizeStoragePath(string $path, string $disk): ?string
    {
        $raw = trim($path);
        if ($raw === '') {
            return null;
        }

        if (! str_starts_with($raw, 'http')) {
            $normalized = ltrim($raw, '/');

            return str_starts_with($normalized, 'storage/')
                ? ltrim(substr($normalized, 8), '/')
                : $normalized;
        }

        $configuredUrl = trim((string) config("filesystems.disks.{$disk}.url", ''));
        if ($configuredUrl === '') {
            $urlPath = parse_url($raw, PHP_URL_PATH);
            if (! is_string($urlPath) || $urlPath === '') {
                return null;
            }
            $normalized = ltrim($urlPath, '/');

            return str_starts_with($normalized, 'storage/')
                ? ltrim(substr($normalized, 8), '/')
                : $normalized;
        }

        $normalizedConfigured = rtrim($configuredUrl, '/');
        if (! str_starts_with($raw, $normalizedConfigured)) {
            $urlPath = parse_url($raw, PHP_URL_PATH);
            if (! is_string($urlPath) || $urlPath === '') {
                return null;
            }
            $normalized = ltrim($urlPath, '/');

            return str_starts_with($normalized, 'storage/')
                ? ltrim(substr($normalized, 8), '/')
                : $normalized;
        }

        $suffix = substr($raw, strlen($normalizedConfigured));
        $suffix = ltrim((string) $suffix, '/');

        return $suffix !== '' ? $suffix : null;
    }

    /**
     * Replace file: xóa path cũ (nếu có), lưu file mới.
     */
    public static function replaceUploadedFile(
        ?string $oldPath,
        UploadedFile $file,
        string $disk,
        string $directory,
        ?string $filename = null
    ): string {
        self::deleteIfExists($oldPath, $disk);

        return self::storeUploadedFile($file, $disk, $directory, $filename);
    }

    /**
     * Update một field path trên model (và save).
     */
    public static function updateModelFilePath(
        Model $model,
        string $attribute,
        string $path
    ): void {
        $model->{$attribute} = $path;
        $model->save();
    }

    /**
     * Update ảnh cho model:
     * - validate extension
     * - xóa ảnh cũ
     * - lưu ảnh mới vào thư mục chuẩn trên media disk (utc-elibrary/... theo UploadDirectory)
     * - gán path vào field và save
     *
     * @throws \InvalidArgumentException
     */
    public static function updateModelImage(
        Model $model,
        UploadedFile $file,
        string $table,
        string $attribute,
        ?string $baseName = null,
        string $disk = 'public',
        ?string $directory = null
    ): string {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException(__('File không hợp lệ.'));
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(__('Chỉ chấp nhận ảnh: ').implode(', ', self::IMAGE_EXTENSIONS).'.');
        }

        self::deleteIfExists((string) ($model->{$attribute} ?? null), $disk);

        $baseName ??= $model->code ?? (string) $model->id;
        $directory ??= UploadDirectory::forTable($table);
        $path = $file->storeAs(trim($directory, '/'), $baseName.'.'.$ext, $disk);

        $model->{$attribute} = $path;
        $model->save();

        return $path;
    }

    public static function getFileExtension(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            return strtolower($file->getClientOriginalExtension() ?: '');
        }

        return strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
    }

    public static function isExcelFile(UploadedFile|string $file): bool
    {
        return in_array(self::getFileExtension($file), self::EXCEL_EXTENSIONS, true);
    }

    /**
     * Đọc Excel/CSV thành array rows với header normalize.
     *
     * @return array{headers: string[], rows: array<int,array<string,?string>>, total_rows: int}
     */
    public static function readExcel(UploadedFile|string $file, int $headerRow = 1, ?int $sheetIndex = 0): array
    {
        $filePath = $file instanceof UploadedFile ? ($file->getRealPath() ?: '') : (string) $file;
        if ($filePath === '' || ! is_file($filePath)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getSheet($sheetIndex ?? 0);
        $data = $worksheet->toArray(null, true, true, true);

        if (empty($data) || ! isset($data[$headerRow])) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $rawHeaders = $data[$headerRow];
        $headers = self::normalizeHeaders($rawHeaders);

        $rows = [];
        foreach ($data as $rowIndex => $rowData) {
            if ($rowIndex <= $headerRow) {
                continue;
            }
            $values = array_values($rowData);
            if (self::isEmptyRow($values)) {
                continue;
            }

            $mapped = [];
            foreach ($headers as $colLetter => $headerName) {
                $mapped[$headerName] = isset($rowData[$colLetter]) ? trim((string) $rowData[$colLetter]) : null;
            }
            $mapped['_row_number'] = (string) $rowIndex;
            $rows[] = $mapped;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'headers' => array_values($headers),
            'rows' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Đọc Excel/CSV theo từng lô để giảm dùng RAM với file lớn.
     *
     * @param  callable(array<int,array<string,?string>>): void  $onChunk
     */
    public static function readExcelInChunks(
        UploadedFile|string $file,
        callable $onChunk,
        int $headerRow = 1,
        int $chunkSize = 1000,
        ?int $sheetIndex = 0
    ): void {
        $filePath = $file instanceof UploadedFile ? ($file->getRealPath() ?: '') : (string) $file;
        if ($filePath === '' || ! is_file($filePath)) {
            return;
        }

        $worksheetIndex = max(0, (int) ($sheetIndex ?? 0));
        $chunkSize = max(100, $chunkSize);

        $filter = new ExcelChunkReadFilter($headerRow);
        for ($startRow = $headerRow + 1; $startRow < PHP_INT_MAX; $startRow += $chunkSize) {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter($filter);
            $filter->setRows($startRow, $chunkSize);

            $spreadsheet = $reader->load($filePath);
            if ($worksheetIndex >= $spreadsheet->getSheetCount()) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                return;
            }
            $worksheet = $spreadsheet->getSheet($worksheetIndex);
            $data = $worksheet->toArray(null, true, true, true);
            if (empty($data) || ! isset($data[$headerRow])) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                continue;
            }

            $headers = self::normalizeHeaders($data[$headerRow]);
            $rows = [];
            foreach ($data as $rowIndex => $rowData) {
                if ($rowIndex <= $headerRow) {
                    continue;
                }
                $values = array_values($rowData);
                if (self::isEmptyRow($values)) {
                    continue;
                }

                $mapped = [];
                foreach ($headers as $colLetter => $headerName) {
                    $mapped[$headerName] = isset($rowData[$colLetter]) ? trim((string) $rowData[$colLetter]) : null;
                }
                $mapped['_row_number'] = (string) $rowIndex;
                $rows[] = $mapped;
            }

            if ($rows !== []) {
                $onChunk($rows);
            } else {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                break;
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * Download Excel/CSV (stream) từ mảng rows.
     */
    public static function downloadExcel(array $data, string $filename = 'export.xlsx', ?array $headers = null): StreamedResponse
    {
        $spreadsheet = self::createSpreadsheet($data, $headers);
        $ext = self::getFileExtension($filename);

        if ($ext === 'csv') {
            $writer = new Csv($spreadsheet);
            $contentType = 'text/csv';
        } else {
            $writer = new Xlsx($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Download một workbook (nhiều sheet).
     *
     * @param  array<int,array{title:string,headers?:array<int,string>,rows?:array<int,array<int,mixed>>}>  $sheets
     */
    public static function downloadWorkbook(array $sheets, string $filename = 'template.xlsx'): StreamedResponse
    {
        $spreadsheet = self::createWorkbook($sheets);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * @param  array<int,array{title:string,headers?:array<int,string>,rows?:array<int,array<int,mixed>>}>  $sheets
     */
    public static function createWorkbook(array $sheets): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $defs = array_values($sheets);
        if (empty($defs)) {
            return $spreadsheet;
        }
        $first = $defs[0];
        $sheet0 = $spreadsheet->getActiveSheet();
        $sheet0->setTitle((string) ($first['title'] ?? 'Sheet 1'));
        self::fillWorksheetTable($sheet0, $first['rows'] ?? [], $first['headers'] ?? null);
        for ($i = 1; $i < count($defs); $i++) {
            $def = $defs[$i];
            $ws = new Worksheet($spreadsheet, (string) ($def['title'] ?? ('Sheet '.($i + 1))));
            $spreadsheet->addSheet($ws);
            self::fillWorksheetTable($ws, $def['rows'] ?? [], $def['headers'] ?? null);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public static function createSpreadsheet(array $data, ?array $headers = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Sheet 1');
        self::fillWorksheetTable($worksheet, $data, $headers);

        return $spreadsheet;
    }

    private static function fillWorksheetTable(Worksheet $worksheet, array $data, ?array $headers): void
    {
        $worksheet->getStyle($worksheet->calculateWorksheetDimension())->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);

        $rowIndex = 1;

        if ($headers === null && ! empty($data)) {
            $firstRow = reset($data);
            if (is_array($firstRow) && count(array_filter(array_keys($firstRow), 'is_string')) > 0) {
                $headers = array_keys($firstRow);
            }
        }

        if (! empty($headers)) {
            $colIndex = 1;
            foreach ($headers as $header) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $worksheet->setCellValue($colLetter.$rowIndex, is_string($header) ? $header : '');
                $colIndex++;
            }

            $lastColIndex = count($headers);
            $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

            $worksheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD'],
                ],
            ]);
            $worksheet->getRowDimension(1)->setRowHeight(24);
            $rowIndex++;
        }

        $lastColIndex = 1;
        if (! empty($data)) {
            $dataValues = [];
            foreach ($data as $row) {
                $dataValues[] = is_array($row) ? array_values($row) : (array) $row;
            }

            $itemCountRow = ! empty($dataValues[0]) ? count($dataValues[0]) : 1;
            $lastColIndex = max(! empty($headers) ? count($headers) : 1, $itemCountRow);

            $worksheet->fromArray($dataValues, null, 'A'.$rowIndex, true);

            $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);
            $lastRow = $rowIndex + count($dataValues) - 1;
            $worksheet->getStyle('A'.$rowIndex.':'.$lastCol.$lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);
        }

        for ($i = 1; $i <= $lastColIndex; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Normalize headers: trim, lowercase, bỏ khoảng trắng thừa.
     *
     * @return array<string,string> [colLetter => normalizedName]
     */
    public static function normalizeHeaders(array $rawHeaders): array
    {
        $headers = [];
        foreach ($rawHeaders as $colLetter => $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $normalized = mb_strtolower(trim((string) $value));
            $normalized = preg_replace('/\s+/', ' ', $normalized) ?: $normalized;
            $headers[$colLetter] = $normalized;
        }

        return $headers;
    }

    public static function isEmptyRow(array $values): bool
    {
        foreach ($values as $val) {
            if ($val !== null && trim((string) $val) !== '') {
                return false;
            }
        }

        return true;
    }

    public static function buildImportResult(int $success, int $skipped, array $errors): array
    {
        return [
            'status' => empty($errors) ? 'success' : (($success > 0) ? 'partial' : 'error'),
            'summary' => [
                'total_processed' => $success + $skipped + count($errors),
                'success' => $success,
                'skipped' => $skipped,
                'errors' => count($errors),
            ],
            'errors' => array_slice($errors, 0, 50),
        ];
    }

    public static function getValueByAliases(array $row, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $alias = mb_strtolower(trim((string) $alias));
            if (isset($row[$alias]) && trim((string) $row[$alias]) !== '') {
                return trim((string) $row[$alias]);
            }
        }

        return null;
    }

    public static function parseNumber(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $cleaned = preg_replace('/[^\d.,-]/', '', (string) $value) ?? '';
        if ($cleaned === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $cleaned)) {
            $cleaned = str_replace(['.', ','], '', $cleaned);
        } elseif (str_contains($cleaned, ',')) {
            $cleaned = str_replace(',', '.', $cleaned);
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    public static function parseYear(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $year = (int) trim((string) $value);
        $currentYear = (int) now()->year;

        return ($year >= 1900 && $year <= $currentYear) ? $year : null;
    }

    /**
     * Tạo ZIP từ danh sách path trên Storage.
     *
     * @param  string[]  $paths  Relative paths theo disk
     */
    public static function createZipFromDiskPaths(
        array $paths,
        string $zipPath,
        string $disk = 'public',
        bool $deleteSources = false
    ): bool {
        if (empty($paths)) {
            return false;
        }
        $diskRef = Storage::disk($disk);
        $zipFullPath = $diskRef->path($zipPath);
        $zipDir = dirname($zipFullPath);
        if (! is_dir($zipDir)) {
            @mkdir($zipDir, 0775, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipFullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $added = 0;
        foreach ($paths as $p) {
            if (! $diskRef->exists($p)) {
                continue;
            }
            $zip->addFile($diskRef->path($p), basename($p));
            $added++;
        }
        $zip->close();

        if ($added === 0) {
            @unlink($zipFullPath);

            return false;
        }

        if ($deleteSources) {
            $diskRef->delete($paths);
        }

        return true;
    }

    /**
     * Giải nén zip upload ra thư mục tạm (storage/app/tmp/...).
     * Caller tự cleanup bằng removeDirectory().
     */
    public static function extractZipToTemp(UploadedFile $zipFile, string $prefix = 'tmp'): string
    {
        if (! $zipFile->isValid()) {
            throw new \InvalidArgumentException(__('File zip không hợp lệ.'));
        }
        $ext = strtolower($zipFile->getClientOriginalExtension() ?: '');
        if ($ext !== 'zip') {
            throw new \InvalidArgumentException(__('Chỉ chấp nhận file .zip.'));
        }

        $tmpDir = storage_path('app/tmp/'.$prefix.'-'.Str::uuid()->toString());
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new \InvalidArgumentException(__('Không thể đọc file zip.'));
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        return $tmpDir;
    }

    public static function removeDirectory(string $dir): void
    {
        if ($dir === '' || ! is_dir($dir)) {
            return;
        }
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getPathname());
                } else {
                    @unlink($file->getPathname());
                }
            }
            @rmdir($dir);
        } catch (\Throwable) {
        }
    }
}
