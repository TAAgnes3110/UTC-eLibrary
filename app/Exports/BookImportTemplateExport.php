<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Classification;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name']);
        $cabinets = StorageCabinet::query()
            ->with(['warehouse:id,code', 'classification:id,code'])
            ->orderBy('code')
            ->get(['code', 'name', 'warehouse_id', 'classification_id']);

        $sheet1Headers = [
            'Số đăng ký cá biệt',
            'Phân loại sách (*)',
            'Tên sách (*)',
            'Loại sách (0: GT, 1: TK)',
            'Tác giả',
            'Nhà xuất bản',
            'Kho sách',
            'Tủ sách',
            'Năm xuất bản',
            'Số trang',
            'Khổ sách',
            'Giá tiền (VND)',
            'Số lượng (*)',
            'Tóm tắt',
            'Ghi chú',
            'Mã sách',
        ];

        $sheets = [
            [
                'title' => 'Sheet0_HuongDan',
                'headers' => ['Mục', 'Nội dung'],
                'rows' => [
                    ['Mục tiêu', 'Nhập sách in (giáo trình & tham khảo) hàng loạt cho UTC eLibrary.'],
                    ['Phạm vi', 'Chỉ hỗ trợ 2 loại: 0 = Giáo trình (GT), 1 = Tham khảo (TK). Không nhập tài liệu số ở đây.'],
                    ['Nguyên tắc an toàn', 'Import all-or-nothing: 1 dòng lỗi là rollback toàn bộ.'],
                    ['Bắt buộc (Sheet1)', 'Tên sách (*), Số lượng (*) > 0, Phân loại sách (*).'],
                    ['Khuyến nghị', 'Kho sách (mã KHO-GT / KHO-TK), Loại sách (0 hoặc 1), Tác giả, NXB.'],
                    ['Mã tự động', 'Số đăng ký cá biệt và Mã sách có thể để trống — hệ thống tự sinh theo kho.'],
                    ['Kho / Tủ', 'Kho có thể để trống (tự tạo). Tủ sách tùy chọn; có thể bổ sung Sheet4 trước import.'],
                    ['Nhập dữ liệu', 'Bắt đầu từ dòng 2 Sheet1_Sach (dòng 1 là tiêu đề cột). Không chèn dòng ghi chú giữa header và dữ liệu.'],
                    ['Sheet phụ', 'Sheet2: phân loại | Sheet3: kho | Sheet4: tủ lưu trữ.'],
                    ['Màu cam / xanh / dương', 'Cam = bắt buộc | Xanh lá = tùy chọn | Xanh dương = hệ thống tự sinh.'],
                ],
            ],
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
                'title' => 'Sheet3_KhoSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => $warehouses->map(fn ($w) => [$w->code, $w->name])->all(),
            ],
            [
                'title' => 'Sheet4_TuSach',
                'headers' => ['Mã tủ', 'Tên tủ', 'Mã kho', 'Mã phân loại'],
                'rows' => $cabinets->map(function ($cabinet) {
                    return [
                        $cabinet->code,
                        $cabinet->name,
                        (string) ($cabinet->warehouse?->code ?? ''),
                        (string) ($cabinet->classification?->code ?? ''),
                    ];
                })->all(),
            ],
        ];

        $spreadsheet = FileHelpers::createWorkbook($sheets);
        $sheet1 = $spreadsheet->getSheetByName('Sheet1_Sach');
        $sheet0 = $spreadsheet->getSheetByName('Sheet0_HuongDan');
        $sheet2 = $spreadsheet->getSheetByName('Sheet2_PhanLoaiSach');
        $sheet3 = $spreadsheet->getSheetByName('Sheet3_KhoSach');
        $sheet4 = $spreadsheet->getSheetByName('Sheet4_TuSach');
        if (! $sheet0 || ! $sheet1 || ! $sheet2 || ! $sheet3 || ! $sheet4) {
            throw new \RuntimeException('Không tìm thấy đủ các sheet mẫu import.');
        }

        $maxClassificationRow = max(2, $sheet2->getHighestRow());
        $maxWarehouseRow = max(2, $sheet3->getHighestRow());
        $maxCabinetRow = max(2, $sheet4->getHighestRow());

        $classificationListFormula = sprintf('=\'%s\'!$A$2:$A$%d', $sheet2->getTitle(), $maxClassificationRow);
        $warehouseListFormula = sprintf('=\'%s\'!$A$2:$A$%d', $sheet3->getTitle(), $maxWarehouseRow);
        $cabinetListFormula = sprintf('=\'%s\'!$B$2:$B$%d', $sheet4->getTitle(), $maxCabinetRow);

        $dvClassification = new DataValidation;
        $dvClassification->setType(DataValidation::TYPE_LIST);
        $dvClassification->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $dvClassification->setAllowBlank(false);
        $dvClassification->setShowInputMessage(true);
        $dvClassification->setShowErrorMessage(true);
        $dvClassification->setShowDropDown(true);
        $dvClassification->setPromptTitle('Phân loại');
        $dvClassification->setPrompt('Chọn mã phân loại (Sheet2) hoặc nhập mã mới.');
        $dvClassification->setErrorTitle('Phân loại chưa hợp lệ');
        $dvClassification->setError('Bổ sung phân loại vào Sheet2 rồi chọn lại.');
        $dvClassification->setFormula1($classificationListFormula);

        $dvWarehouse = new DataValidation;
        $dvWarehouse->setType(DataValidation::TYPE_LIST);
        $dvWarehouse->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $dvWarehouse->setAllowBlank(true);
        $dvWarehouse->setShowInputMessage(true);
        $dvWarehouse->setShowErrorMessage(true);
        $dvWarehouse->setShowDropDown(true);
        $dvWarehouse->setPromptTitle('Kho sách');
        $dvWarehouse->setPrompt('Chọn KHO-GT / KHO-TK hoặc để trống (hệ thống tự tạo).');
        $dvWarehouse->setErrorTitle('Kho sách chưa hợp lệ');
        $dvWarehouse->setError('Bổ sung kho vào Sheet3 rồi chọn lại.');
        $dvWarehouse->setFormula1($warehouseListFormula);

        $dvResourceType = new DataValidation;
        $dvResourceType->setType(DataValidation::TYPE_LIST);
        $dvResourceType->setErrorStyle(DataValidation::STYLE_STOP);
        $dvResourceType->setAllowBlank(true);
        $dvResourceType->setShowInputMessage(true);
        $dvResourceType->setShowErrorMessage(true);
        $dvResourceType->setShowDropDown(true);
        $dvResourceType->setPromptTitle('Loại sách');
        $dvResourceType->setPrompt('0 = Giáo trình, 1 = Tham khảo.');
        $dvResourceType->setErrorTitle('Loại sách không hợp lệ');
        $dvResourceType->setError('Chỉ nhập 0 (Giáo trình) hoặc 1 (Tham khảo).');
        $dvResourceType->setFormula1('"0,1"');

        $dvCabinet = null;
        if ($maxCabinetRow >= 2) {
            $dvCabinet = new DataValidation;
            $dvCabinet->setType(DataValidation::TYPE_LIST);
            $dvCabinet->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dvCabinet->setAllowBlank(true);
            $dvCabinet->setShowInputMessage(true);
            $dvCabinet->setShowErrorMessage(true);
            $dvCabinet->setShowDropDown(true);
            $dvCabinet->setPromptTitle('Tủ sách');
            $dvCabinet->setPrompt('Tùy chọn — chọn tủ có sẵn hoặc nhập tên tủ.');
            $dvCabinet->setFormula1($cabinetListFormula);
        }

        $applyRows = 300;
        for ($r = 2; $r <= $applyRows; $r++) {
            $sheet1->getCell('B'.$r)->setDataValidation(clone $dvClassification);
            $sheet1->getCell('D'.$r)->setDataValidation(clone $dvResourceType);
            $sheet1->getCell('G'.$r)->setDataValidation(clone $dvWarehouse);
            if ($dvCabinet instanceof DataValidation) {
                $sheet1->getCell('H'.$r)->setDataValidation(clone $dvCabinet);
            }
        }

        self::applyProfessionalTemplateStyles($spreadsheet, $sheet0, $sheet1, $sheet2, $sheet3, $sheet4);
        $spreadsheet->setActiveSheetIndexByName('Sheet1_Sach');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'Mau_nhap_sach.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private static function applyProfessionalTemplateStyles(
        Spreadsheet $spreadsheet,
        Worksheet $sheet0,
        Worksheet $sheet1,
        Worksheet $sheet2,
        Worksheet $sheet3,
        Worksheet $sheet4
    ): void {
        $requiredFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF59E0B']];
        $optionalFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF10B981']];
        $autoFill = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF3B82F6']];

        $sheet0->freezePane('A2');
        $sheet0->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet0->getColumnDimension('A')->setWidth(26);
        $sheet0->getColumnDimension('B')->setWidth(110);
        $sheet0->getStyle('A1:B200')->getAlignment()->setWrapText(true);

        $sheet1->freezePane('A2');
        $sheet1ColumnWidths = [
            'A' => 14,
            'B' => 16,
            'C' => 26,
            'D' => 12,
            'E' => 18,
            'F' => 16,
            'G' => 12,
            'H' => 18,
            'I' => 10,
            'J' => 8,
            'K' => 10,
            'L' => 11,
            'M' => 10,
            'N' => 18,
            'O' => 14,
            'P' => 12,
        ];
        foreach ($sheet1ColumnWidths as $col => $width) {
            $sheet1->getColumnDimension($col)->setWidth($width);
        }
        $sheet1->getRowDimension(1)->setRowHeight(32);
        $sheet1->getStyle('A1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('A1:P1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet1->getStyle('A1:P300')->getAlignment()->setWrapText(false);

        // Cột tự sinh nếu để trống.
        foreach (['A1', 'G1', 'P1'] as $cell) {
            $sheet1->getStyle($cell)->getFill()->applyFromArray($autoFill);
        }
        // Cột bắt buộc.
        foreach (['B1', 'C1', 'M1'] as $cell) {
            $sheet1->getStyle($cell)->getFill()->applyFromArray($requiredFill);
        }
        // Cột tùy chọn/tra cứu.
        foreach (['D1', 'E1', 'F1', 'H1', 'I1', 'J1', 'K1', 'L1', 'N1', 'O1'] as $cell) {
            $sheet1->getStyle($cell)->getFill()->applyFromArray($optionalFill);
        }

        foreach ([$sheet2, $sheet3, $sheet4] as $lookupSheet) {
            $highestCol = $lookupSheet->getHighestColumn();
            $lookupSheet->freezePane('A2');
            $lookupSheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);
            $lookupSheet->getStyle("A1:{$highestCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $lookupSheet->getStyle("A1:{$highestCol}1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $lookupSheet->getStyle("A1:{$highestCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
            $lookupSheet->getStyle("A1:{$highestCol}1")->getFont()->getColor()->setARGB('FFFFFFFF');
            foreach (range(1, Coordinate::columnIndexFromString($highestCol)) as $idx) {
                $lookupSheet->getColumnDimension(Coordinate::stringFromColumnIndex($idx))->setAutoSize(true);
            }
        }
    }
}
