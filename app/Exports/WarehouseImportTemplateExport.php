<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WarehouseImportTemplateExport
{
    public static function stream(): StreamedResponse
    {
        return FileHelpers::downloadExcel([], 'Mẫu nhập kho sách.xlsx', ['Mã', 'Tên']);
    }
}
