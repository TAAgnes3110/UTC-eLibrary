<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Classification;
use App\Models\Warehouse;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BookImportTemplateExport
{
    public static function stream(): StreamedResponse
    {
        $classifications = Classification::query()
            ->roots()
            ->orderBy('code')
            ->get(['code', 'name']);

        $warehouses = Warehouse::query()
            ->orderBy('code')
            ->get(['code', 'name']);

        $sheet1Headers = [
            'Số đăng ký cá biệt',
            'Phân loại sách (*)',
            'Tên sách (*)',
            'Loại sách (0: giáo trình, 1: tham khảo, 2: tài liệu số)',
            'Tác giả (ngăn cách bằng dấu , hoặc ;)',
            'Nhà xuất bản (ngăn cách bằng dấu , hoặc ;)',
            'Kho sách (*)',
            'Tủ sách',
            'Năm xuất bản',
            'Ngôn ngữ',
            'Số trang',
            'Khổ sách',
            'Giá tiền',
            'Số lượng (*)',
            'Tóm tắt',
            'Ghi chú',
        ];

        $sheets = [
            [
                'title' => 'Sheet1_Sach',
                'headers' => $sheet1Headers,
                'rows' => [],
            ],
            [
                'title' => 'Sheet2_PhanLoaiSach',
                'headers' => ['Mã - Tên (hiển thị)', 'Mã', 'Tên'],
                'rows' => $classifications->map(function ($c) {
                    $display = trim(sprintf('%s - %s', (string) $c->code, (string) $c->name), ' -');

                    return [$display, $c->code, $c->name];
                })->all(),
            ],
            [
                'title' => 'Sheet3_KhoSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => $warehouses->map(fn ($w) => [$w->code, $w->name])->all(),
            ],
        ];

        $spreadsheet = FileHelpers::createWorkbook($sheets);
        $sheet1 = $spreadsheet->getSheetByName('Sheet1_Sach');
        $sheet2 = $spreadsheet->getSheetByName('Sheet2_PhanLoaiSach');
        $sheet3 = $spreadsheet->getSheetByName('Sheet3_KhoSach');
        if (! $sheet1 || ! $sheet2 || ! $sheet3) {
            throw new \RuntimeException('Không tìm thấy đủ các sheet mẫu import.');
        }

        $maxClassificationRow = max(2, $sheet2->getHighestRow());
        $maxWarehouseRow = max(2, $sheet3->getHighestRow());

        $classificationListFormula = sprintf('=\'%s\'!$A$2:$A$%d', $sheet2->getTitle(), $maxClassificationRow);
        $warehouseListFormula = sprintf('=\'%s\'!$A$2:$A$%d', $sheet3->getTitle(), $maxWarehouseRow);

        $applyRows = 2000;
        for ($r = 2; $r <= $applyRows; $r++) {
            $dvClassification = new DataValidation;
            $dvClassification->setType(DataValidation::TYPE_LIST);
            $dvClassification->setErrorStyle(DataValidation::STYLE_STOP);
            $dvClassification->setAllowBlank(false);
            $dvClassification->setShowInputMessage(true);
            $dvClassification->setShowErrorMessage(true);
            $dvClassification->setShowDropDown(true);
            $dvClassification->setFormula1($classificationListFormula);
            // Column B: Phân loại sách (*)
            $sheet1->getCell('B'.$r)->setDataValidation(clone $dvClassification);

            $dvWarehouse = new DataValidation;
            $dvWarehouse->setType(DataValidation::TYPE_LIST);
            $dvWarehouse->setErrorStyle(DataValidation::STYLE_STOP);
            $dvWarehouse->setAllowBlank(false);
            $dvWarehouse->setShowInputMessage(true);
            $dvWarehouse->setShowErrorMessage(true);
            $dvWarehouse->setShowDropDown(true);
            $dvWarehouse->setFormula1($warehouseListFormula);
            // Column G: Kho sách (*)
            $sheet1->getCell('G'.$r)->setDataValidation(clone $dvWarehouse);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'Mau_nhap_sach.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
