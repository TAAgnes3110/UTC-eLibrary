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
use PhpOffice\PhpSpreadsheet\Style\Border;
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
            "Số đăng ký cá biệt\n(Để trống = hệ thống tự sinh)",
            "Phân loại sách (*)\nNhập mã hoặc tên (xem sheet 2)",
            "Tên sách (*)\nBắt buộc khi thêm mới",
            "Loại sách\n0: Giáo trình, 1: Tham khảo, 2: Tài liệu số",
            "Tác giả\nNhiều tác giả ngăn cách bằng ';'",
            "Nhà xuất bản\nNhiều NXB ngăn cách bằng ';'",
            "Kho sách\nCó thể để trống để hệ thống tự tạo (xem sheet 3)",
            "Tủ sách\nTùy chọn - có thể thêm ở sheet 4",
            'Năm xuất bản',
            'Số trang',
            "Khổ sách\nVD: 24x17cm",
            "Giá tiền\nĐơn vị: VND",
            "Số lượng (*)\nPhải > 0",
            'Tóm tắt',
            'Ghi chú',
            "Mã sách\nTùy chọn - để trống hệ thống tự sinh",
        ];

        $sheets = [
            [
                'title' => 'Sheet0_HuongDan',
                'headers' => ['Mục', 'Nội dung'],
                'rows' => [
                    ['Mục tiêu', 'Nhập sách hàng loạt theo chuẩn UTC eLibrary.'],
                    ['Nguyên tắc an toàn', 'Import chạy all-or-nothing: chỉ cần 1 dòng lỗi là rollback toàn bộ.'],
                    ['Bắt buộc nhập', 'Sheet1: Tên sách, Số lượng. Nếu là sách giấy cần thêm Phân loại và Kho.'],
                    ['Mã tự động', 'Mã kho và Mã sách có thể để trống; hệ thống sẽ tự tạo khi cần.'],
                    ['Bổ sung danh mục', 'Bạn có thể thêm Phân loại/Kho/Tủ ngay tại Sheet2/3/4 trước khi import.'],
                    ['Nguồn dữ liệu', 'Sheet2 -> bảng classifications, Sheet3 -> warehouses, Sheet4 -> storage_cabinets.'],
                    ['Màu cam', 'Cột bắt buộc nhập trên Sheet1.'],
                    ['Màu xanh lá', 'Cột tùy chọn (khuyến nghị nhập nếu có).'],
                    ['Màu xanh dương', 'Cột hệ thống có thể tự sinh nếu để trống.'],
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

        $applyRows = 2000;
        for ($r = 3; $r <= $applyRows; $r++) {
            $dvClassification = new DataValidation;
            $dvClassification->setType(DataValidation::TYPE_LIST);
            $dvClassification->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dvClassification->setAllowBlank(false);
            $dvClassification->setShowInputMessage(true);
            $dvClassification->setShowErrorMessage(true);
            $dvClassification->setShowDropDown(true);
            $dvClassification->setPromptTitle('Phân loại');
            $dvClassification->setPrompt('Chọn mã phân loại có sẵn hoặc thêm mới ở Sheet2_PhanLoaiSach.');
            $dvClassification->setErrorTitle('Phân loại chưa hợp lệ');
            $dvClassification->setError('Hãy bổ sung phân loại vào Sheet2 rồi chọn lại.');
            $dvClassification->setFormula1($classificationListFormula);
            // Column B: Phân loại sách (*)
            $sheet1->getCell('B'.$r)->setDataValidation(clone $dvClassification);

            $dvWarehouse = new DataValidation;
            $dvWarehouse->setType(DataValidation::TYPE_LIST);
            $dvWarehouse->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dvWarehouse->setAllowBlank(true);
            $dvWarehouse->setShowInputMessage(true);
            $dvWarehouse->setShowErrorMessage(true);
            $dvWarehouse->setShowDropDown(true);
            $dvWarehouse->setPromptTitle('Kho sách');
            $dvWarehouse->setPrompt('Có thể để trống để hệ thống tự tạo mã kho, hoặc thêm mã ở Sheet3_KhoSach.');
            $dvWarehouse->setErrorTitle('Kho sách chưa hợp lệ');
            $dvWarehouse->setError('Hãy bổ sung kho vào Sheet3 rồi chọn lại.');
            $dvWarehouse->setFormula1($warehouseListFormula);
            // Column G: Kho sách (*)
            $sheet1->getCell('G'.$r)->setDataValidation(clone $dvWarehouse);

            $dvCabinet = new DataValidation;
            $dvCabinet->setType(DataValidation::TYPE_LIST);
            $dvCabinet->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dvCabinet->setAllowBlank(true);
            $dvCabinet->setShowInputMessage(true);
            $dvCabinet->setShowErrorMessage(true);
            $dvCabinet->setShowDropDown(true);
            $dvCabinet->setPromptTitle('Tủ sách');
            $dvCabinet->setPrompt('Tùy chọn: chọn tên tủ có sẵn hoặc thêm ở Sheet4_TuSach.');
            $dvCabinet->setErrorTitle('Tủ sách chưa hợp lệ');
            $dvCabinet->setError('Hãy bổ sung tủ vào Sheet4 rồi chọn lại.');
            $dvCabinet->setFormula1($cabinetListFormula);
            // Column H: Tủ sách
            $sheet1->getCell('H'.$r)->setDataValidation(clone $dvCabinet);
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
        foreach (range('A', 'P') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->getRowDimension(1)->setRowHeight(56);
        $sheet1->getStyle('A1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('A1:P1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet1->getStyle('A1:P2001')->getAlignment()->setWrapText(true);

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

        $noteRows = [
            ['A2', 'Để trống để hệ thống tự sinh số đăng ký theo kho.'],
            ['B2', 'Chọn mã từ Sheet2 hoặc thêm mới trực tiếp ở Sheet2 trước khi import.'],
            ['G2', 'Có thể để trống để hệ thống tự tạo kho. Nếu nhập, ưu tiên mã trong file này.'],
            ['H2', 'Tùy chọn. Nếu cần tạo tủ mới, thêm vào Sheet4 trước khi import.'],
        ];
        foreach ($noteRows as [$cell, $text]) {
            $sheet1->setCellValue($cell, $text);
        }
        $sheet1->getStyle('A2:P2')->getFont()->setItalic(true)->setSize(10)->getColor()->setARGB('FF334155');
        $sheet1->getStyle('A2:P2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet1->getStyle('A2:P2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

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
