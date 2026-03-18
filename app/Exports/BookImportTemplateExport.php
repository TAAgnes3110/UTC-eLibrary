<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Warehouse;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BookImportTemplateExport
{
    public static function stream(): StreamedResponse
    {
        $classifications = Classification::query()
            ->orderBy('code')
            ->get(['code', 'name']);

        $classificationDetails = ClassificationDetail::query()
            ->with('classification:id,code')
            ->orderBy('classification_id')
            ->orderBy('code')
            ->get(['classification_id', 'code', 'name']);

        $warehouses = Warehouse::query()
            ->orderBy('code')
            ->get(['code', 'name']);

        $sheet1Headers = [
            'Số đăng ký cá biệt',
            'Mã sách',
            'Phân loại sách',
            'Phân loại sách chi tiết',
            'Tên sách (*)',
            'Tác giả (ngăn cách bằng dấu , hoặc ;)',
            'Nhà xuất bản (ngăn cách bằng dấu , hoặc ;)',
            'Kho sách (*)',
            'Năm xuất bản',
            'Ngôn ngữ',
            'Số trang',
            'Khổ sách',
            'Giá tiền',
            'Số lượng (*)',
            'Tủ (Cabinet)',
            'Kệ (Shelf)',
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
                'headers' => ['Mã', 'Tên'],
                'rows' => $classifications->map(fn ($c) => [$c->code, $c->name])->all(),
            ],
            [
                'title' => 'Sheet3_PhanLoaiSachChiTiet',
                'headers' => ['Mã phân loại chính', 'Mã phân loại chi tiết', 'Tên'],
                'rows' => $classificationDetails->map(fn ($d) => [optional($d->classification)->code, $d->code, $d->name])->all(),
            ],
            [
                'title' => 'Sheet4_KhoSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => $warehouses->map(fn ($w) => [$w->code, $w->name])->all(),
            ],
        ];

        $spreadsheet = FileHelpers::createWorkbook($sheets);
        $sheet1 = $spreadsheet->getSheetByName('Sheet1_Sach');
        $sheet2 = $spreadsheet->getSheetByName('Sheet2_PhanLoaiSach');
        $sheet3 = $spreadsheet->getSheetByName('Sheet3_PhanLoaiSachChiTiet');
        $sheet4 = $spreadsheet->getSheetByName('Sheet4_KhoSach');

        $maxClassificationRow = max(2, $sheet2->getHighestRow());
        $maxDetailRow = max(2, $sheet3->getHighestRow());
        $maxWarehouseRow = max(2, $sheet4->getHighestRow());

        $classificationListFormula = sprintf('=\'%s\'!$A$2:$A$%d', $sheet2->getTitle(), $maxClassificationRow);
        $detailListFormula = sprintf('=\'%s\'!$B$2:$B$%d', $sheet3->getTitle(), $maxDetailRow);
        $warehouseListFormula = sprintf('=\'%s\'!$A$2:$A$%d', $sheet4->getTitle(), $maxWarehouseRow);

        $applyRows = 2000;
        for ($r = 2; $r <= $applyRows; $r++) {
            $dvClassification = new DataValidation();
            $dvClassification->setType(DataValidation::TYPE_LIST);
            $dvClassification->setErrorStyle(DataValidation::STYLE_STOP);
            $dvClassification->setAllowBlank(true);
            $dvClassification->setShowInputMessage(true);
            $dvClassification->setShowErrorMessage(true);
            $dvClassification->setShowDropDown(true);
            $dvClassification->setFormula1($classificationListFormula);
            // Column C: Phân loại sách
            $sheet1->getCell('C' . $r)->setDataValidation(clone $dvClassification);

            $dvDetail = new DataValidation();
            $dvDetail->setType(DataValidation::TYPE_LIST);
            $dvDetail->setErrorStyle(DataValidation::STYLE_STOP);
            $dvDetail->setAllowBlank(true);
            $dvDetail->setShowInputMessage(true);
            $dvDetail->setShowErrorMessage(true);
            $dvDetail->setShowDropDown(true);
            $dvDetail->setFormula1($detailListFormula);
            // Column D: Phân loại sách chi tiết
            $sheet1->getCell('D' . $r)->setDataValidation(clone $dvDetail);

            $dvWarehouse = new DataValidation();
            $dvWarehouse->setType(DataValidation::TYPE_LIST);
            $dvWarehouse->setErrorStyle(DataValidation::STYLE_STOP);
            $dvWarehouse->setAllowBlank(true);
            $dvWarehouse->setShowInputMessage(true);
            $dvWarehouse->setShowErrorMessage(true);
            $dvWarehouse->setShowDropDown(true);
            $dvWarehouse->setFormula1($warehouseListFormula);
            // Column H: Kho sách
            $sheet1->getCell('H' . $r)->setDataValidation(clone $dvWarehouse);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'Mau_nhap_sach.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

