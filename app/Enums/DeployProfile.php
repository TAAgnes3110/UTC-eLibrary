<?php

namespace App\Enums;

/**
 * Profile triển khai — cùng codebase, chỉ đổi .env khi chuyển host.
 */
enum DeployProfile: string
{
    case Local = 'local';

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
        return [
            'max_pdf_bytes' => 0,
            'assets_disk' => 'local',
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Máy dev (local)',
            self::Vps => 'VPS / Docker',
        };
    }

    public function allowsShellPdfTools(): bool
    {
        return true;
    }

    public function allowsImagickPdf(): bool
    {
        return true;
    }

    public function allowsRuntimePreviewGeneration(): bool
    {
        return true;
    }

    public function runsPostUploadProcessingOnHost(): bool
    {
        return true;
    }
}
