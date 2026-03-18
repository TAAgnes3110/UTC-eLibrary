<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClassificationDetailImportTemplateExport
{
    public static function stream(): StreamedResponse
    {
        return FileHelpers::downloadExcel(
            [],
            'Mẫu nhập phân loại sách chi tiết.xlsx',
            ['Mã phân loại chính', 'Mã phân loại chi tiết', 'Tên phân loại chi tiết']
        );
    }
}

