<?php

namespace App\Enums;

/**
 * Profile triển khai — cùng codebase, chỉ đổi .env khi chuyển host.
 */
enum DeployProfile: string
{
    case Local = 'local';

    case Infinityfree = 'infinityfree';

    case Vps = 'vps';

    public static function tryFromEnv(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Local;
        }

        return self::tryFrom($value) ?? self::Local;
    }

    /**
     * @return array{max_pdf_bytes: int, assets_disk: string}
     */
    public function readerDefaults(): array
    {
        return match ($this) {
            self::Local => [
                'max_pdf_bytes' => 0,
                'assets_disk' => 'local',
            ],
            self::Infinityfree => [
                'max_pdf_bytes' => 20 * 1024 * 1024,
                'assets_disk' => 'local',
            ],
            self::Vps => [
                'max_pdf_bytes' => 0,
                'assets_disk' => 'local',
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Máy dev (local)',
            self::Infinityfree => 'Shared hosting (InfinityFree)',
            self::Vps => 'VPS / Docker',
        };
    }

    public function allowsShellPdfTools(): bool
    {
        return $this !== self::Infinityfree;
    }

    public function allowsImagickPdf(): bool
    {
        return $this !== self::Infinityfree;
    }

    public function allowsRuntimePreviewGeneration(): bool
    {
        return $this !== self::Infinityfree;
    }

    public function runsPostUploadProcessingOnHost(): bool
    {
        return $this !== self::Infinityfree;
    }

    public function prefersSyncQueue(): bool
    {
        return $this === self::Infinityfree;
    }
}
