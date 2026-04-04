<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClassificationImportTemplateExport
{
    public static function stream(): StreamedResponse
    {
        return FileHelpers::downloadExcel([], 'Mẫu nhập phân loại sách.xlsx', ['Mã phân loại', 'Tên phân loại']);
    }
}
