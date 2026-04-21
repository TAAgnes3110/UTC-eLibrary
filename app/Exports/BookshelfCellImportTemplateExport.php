<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\BookshelfCell;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Warehouse;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BookshelfCellImportTemplateExport
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

        $sheets = [
            [
                'title' => 'Sheet1_KeSach',
                'headers' => [
                    'Kho sách',
                    'Vị trí',
                    'Phân loại',
                    'Phân loại chi tiết',
                    'Nhãn',
                ],
                'rows' => [],
            ],
            [
                'title' => 'Sheet2_KhoSach',
                'headers' => ['Mã', 'Tên', 'Hiển thị'],
                'rows' => $warehouses->map(fn ($w) => [$w->code, $w->name, trim($w->code.' - '.$w->name, ' -')])->all(),
            ],
            [
                'title' => 'Sheet3_PhanLoaiSach',
                'headers' => ['Mã', 'Tên', 'Hiển thị'],
                'rows' => $classifications->map(fn ($c) => [$c->code, $c->name, trim($c->code.' - '.$c->name, ' -')])->all(),
            ],
            [
                'title' => 'Sheet4_PhanLoaiSachChiTiet',
                'headers' => ['Mã phân loại chính', 'Mã phân loại chi tiết', 'Tên', 'Hiển thị'],
                'rows' => $classificationDetails->map(fn ($d) => [
                    optional($d->classification)->code,
                    $d->code,
                    $d->name,
                    trim($d->code.' - '.$d->name, ' -'),
                ])->all(),
            ],
            [
                'title' => 'Sheet5_ViTriDanhSach',
                'headers' => ['Mã kho', 'Tên range', 'Kho sách hiển thị', 'Range theo kho'],
                'rows' => [],
            ],
        ];

        $spreadsheet = FileHelpers::createWorkbook($sheets);
        $sheet1 = $spreadsheet->getSheetByName('Sheet1_KeSach');
        $sheet2 = $spreadsheet->getSheetByName('Sheet2_KhoSach');
        $sheet3 = $spreadsheet->getSheetByName('Sheet3_PhanLoaiSach');
        $sheet4 = $spreadsheet->getSheetByName('Sheet4_PhanLoaiSachChiTiet');
        $sheet5 = $spreadsheet->getSheetByName('Sheet5_ViTriDanhSach');

        if ($sheet1 && $sheet2 && $sheet3 && $sheet4 && $sheet5) {
            $warehouseEmptyPositions = self::buildEmptyPositionRows($warehouses);
            $sheet5->setCellValue('D2', '');
            $spreadsheet->addNamedRange(new NamedRange('POS_EMPTY', $sheet5, '$D$2:$D$2'));

            $mapStartRow = 2;
            $dataStartColumn = 4;
            $mapEndRow = $mapStartRow + max(0, count($warehouseEmptyPositions) - 1);
            foreach ($warehouseEmptyPositions as $index => $item) {
                $row = $mapStartRow + $index;
                $warehouseDisplay = (string) ($item['warehouse_display'] ?? '');
                $warehouseCode = (string) ($item['warehouse_code'] ?? '');
                $positions = is_array($item['positions'] ?? null) ? $item['positions'] : [];
                $rangeName = 'POS_'.($index + 1);

                $sheet5->setCellValue('A'.$row, $warehouseCode);
                $sheet5->setCellValue('B'.$row, $rangeName);
                $sheet5->setCellValue('C'.$row, $warehouseDisplay);
                $sheet5->setCellValue('D'.$row, $rangeName);

                $colIndex = $dataStartColumn + $index;
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet5->setCellValue($colLetter.'1', $warehouseDisplay);

                $positionRowStart = 2;
                if ($positions === []) {
                    $positions = [''];
                }
                foreach ($positions as $offset => $position) {
                    $sheet5->setCellValue($colLetter.($positionRowStart + $offset), (string) $position);
                }

                $positionRowEnd = $positionRowStart + count($positions) - 1;
                $spreadsheet->addNamedRange(
                    new NamedRange($rangeName, $sheet5, '$'.$colLetter.'$'.$positionRowStart.':$'.$colLetter.'$'.$positionRowEnd)
                );
            }

            $maxWarehouseRow = max(2, $sheet2->getHighestRow());
            $maxClassificationRow = max(2, $sheet3->getHighestRow());
            $maxDetailRow = max(2, $sheet4->getHighestRow());
            $warehouseListFormula = sprintf('=\'%s\'!$C$2:$C$%d', $sheet2->getTitle(), $maxWarehouseRow);
            $classificationListFormula = sprintf('=\'%s\'!$C$2:$C$%d', $sheet3->getTitle(), $maxClassificationRow);
            $detailListFormula = sprintf('=\'%s\'!$D$2:$D$%d', $sheet4->getTitle(), $maxDetailRow);

            for ($r = 2; $r <= 3000; $r++) {
                $dvWarehouse = new DataValidation;
                $dvWarehouse->setType(DataValidation::TYPE_LIST);
                $dvWarehouse->setErrorStyle(DataValidation::STYLE_STOP);
                $dvWarehouse->setAllowBlank(false);
                $dvWarehouse->setShowInputMessage(true);
                $dvWarehouse->setShowErrorMessage(true);
                $dvWarehouse->setShowDropDown(true);
                $dvWarehouse->setErrorTitle('Giá trị không hợp lệ');
                $dvWarehouse->setError('Vui lòng chọn giá trị trong danh sách kho.');
                $dvWarehouse->setFormula1($warehouseListFormula);
                $sheet1->getCell('A'.$r)->setDataValidation(clone $dvWarehouse);

                $dvPosition = new DataValidation;
                $dvPosition->setType(DataValidation::TYPE_LIST);
                $dvPosition->setErrorStyle(DataValidation::STYLE_STOP);
                $dvPosition->setAllowBlank(false);
                $dvPosition->setShowInputMessage(true);
                $dvPosition->setShowErrorMessage(true);
                $dvPosition->setShowDropDown(true);
                $dvPosition->setErrorTitle('Giá trị không hợp lệ');
                $dvPosition->setError('Vui lòng chọn vị trí trống trong danh sách.');
                $dvPosition->setFormula1(sprintf(
                    '=INDIRECT(IFERROR(VLOOKUP($A%d;\'%s\'!$C$2:$D$%d;2;FALSE);"POS_EMPTY"))',
                    $r,
                    $sheet5->getTitle(),
                    $mapEndRow >= $mapStartRow ? $mapEndRow : 2
                ));
                $sheet1->getCell('B'.$r)->setDataValidation(clone $dvPosition);

                $dvClassification = new DataValidation;
                $dvClassification->setType(DataValidation::TYPE_LIST);
                $dvClassification->setErrorStyle(DataValidation::STYLE_STOP);
                $dvClassification->setAllowBlank(true);
                $dvClassification->setShowInputMessage(true);
                $dvClassification->setShowErrorMessage(true);
                $dvClassification->setShowDropDown(true);
                $dvClassification->setErrorTitle('Giá trị không hợp lệ');
                $dvClassification->setError('Vui lòng chọn phân loại trong danh sách.');
                $dvClassification->setFormula1($classificationListFormula);
                $sheet1->getCell('C'.$r)->setDataValidation(clone $dvClassification);

                $dvDetail = new DataValidation;
                $dvDetail->setType(DataValidation::TYPE_LIST);
                $dvDetail->setErrorStyle(DataValidation::STYLE_STOP);
                $dvDetail->setAllowBlank(true);
                $dvDetail->setShowInputMessage(true);
                $dvDetail->setShowErrorMessage(true);
                $dvDetail->setShowDropDown(true);
                $dvDetail->setErrorTitle('Giá trị không hợp lệ');
                $dvDetail->setError('Vui lòng chọn phân loại chi tiết trong danh sách.');
                $dvDetail->setFormula1($detailListFormula);
                $sheet1->getCell('D'.$r)->setDataValidation(clone $dvDetail);
                $sheet1->setCellValue('E'.$r, '=IF(B'.$r.'="", "", B'.$r.')');
            }
            $sheet1->freezePane('A2');
            $sheet1->getStyle('A1:E1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFF3B0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                ],
            ]);
            $sheet1->getRowDimension(1)->setRowHeight(32);
            $sheet1->getDefaultRowDimension()->setRowHeight(22);
            $sheet1->getColumnDimension('A')->setWidth(24);
            $sheet1->getColumnDimension('B')->setWidth(20);
            $sheet1->getColumnDimension('C')->setWidth(28);
            $sheet1->getColumnDimension('D')->setWidth(32);
            $sheet1->getColumnDimension('E')->setWidth(24);

            $sheet1->getComment('A1')->getText()->createTextRun(
                "Kho sách (bắt buộc):\n- Chọn theo danh sách gợi ý.\n- Có thể nhập mã, tên hoặc dạng 'MÃ - TÊN'."
            );
            $sheet1->getComment('A1')->setWidth('220px');
            $sheet1->getComment('A1')->setHeight('95px');

            $sheet1->getComment('B1')->getText()->createTextRun(
                "Vị trí (bắt buộc):\n- Chọn trong danh sách vị trí trống gợi ý.\n- Chọn dạng Rxx-Cyy (VD: R01-C02)."
            );
            $sheet1->getComment('B1')->setWidth('220px');
            $sheet1->getComment('B1')->setHeight('95px');

            $sheet1->getComment('C1')->getText()->createTextRun(
                "Phân loại:\n- Có thể chọn/nhập mã, tên hoặc 'MÃ - TÊN'.\n- Nên nhập để thống kê chính xác."
            );
            $sheet1->getComment('C1')->setWidth('220px');
            $sheet1->getComment('C1')->setHeight('95px');

            $sheet1->getComment('D1')->getText()->createTextRun(
                "Phân loại chi tiết:\n- Có thể chọn/nhập mã, tên hoặc 'MÃ - TÊN'.\n- Nên khớp với phân loại chính."
            );
            $sheet1->getComment('D1')->setWidth('230px');
            $sheet1->getComment('D1')->setHeight('95px');

            $sheet1->getComment('E1')->getText()->createTextRun(
                "Quy tắc nhập nhãn:\n- Hệ thống tự gợi ý theo cột Vị trí.\n- Có thể sửa tay nếu thư viện dùng mã riêng.\n- Nên đặt ngắn gọn, dễ tìm (VD: R01-C02 hoặc K1-A2).\n- Không để trống nếu bạn muốn quản lý theo nhãn riêng."
            );
            $sheet1->getComment('E1')->setWidth('260px');
            $sheet1->getComment('E1')->setHeight('140px');

            $sheet1->setAutoFilter('A1:E1');
            $sheet1->setSelectedCell('A2');

            $headerStyleYellow = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFF3B0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                ],
            ];

            $sheet2->freezePane('A2');
            $sheet3->freezePane('A2');
            $sheet4->freezePane('A2');
            $sheet5->freezePane('A2');
            $sheet5->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            $sheet2->getStyle('A1:C1')->applyFromArray($headerStyleYellow);
            $sheet3->getStyle('A1:C1')->applyFromArray($headerStyleYellow);
            $sheet4->getStyle('A1:D1')->applyFromArray($headerStyleYellow);
            $sheet5->getStyle('A1:D1')->applyFromArray($headerStyleYellow);
            $sheet2->getRowDimension(1)->setRowHeight(30);
            $sheet3->getRowDimension(1)->setRowHeight(30);
            $sheet4->getRowDimension(1)->setRowHeight(30);
            $sheet5->getRowDimension(1)->setRowHeight(30);

            self::setRequiredHeader($sheet1, 'A1', 'Kho sách');
            self::setRequiredHeader($sheet1, 'B1', 'Vị trí');
            self::setRequiredHeader($sheet1, 'C1', 'Phân loại');
            self::setRequiredHeader($sheet1, 'D1', 'Phân loại chi tiết');
            self::setRequiredHeader($sheet1, 'E1', 'Nhãn');
        }

        $spreadsheet->setActiveSheetIndexByName('Sheet1_KeSach');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'MauNhapKeSach.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private static function setRequiredHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $cellRef, string $title): void
    {
        $richText = new RichText;
        $main = $richText->createTextRun($title."\n");
        $main->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF000000'));

        $required = $richText->createTextRun('(*)');
        $required->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFF0000'));

        $sheet->setCellValue($cellRef, $richText);
    }

    /**
     * @param  \Illuminate\Support\Collection<int,\App\Models\Warehouse>  $warehouses
     * @return array<int,array{warehouse_code:string,warehouse_display:string,positions:array<int,string>}>
     */
    private static function buildEmptyPositionRows($warehouses): array
    {
        $occupied = [];
        $cells = BookshelfCell::query()
            ->get(['warehouse_id', 'row_index', 'column_index']);
        foreach ($cells as $cell) {
            $key = (int) $cell->warehouse_id;
            $pos = ((int) $cell->row_index).'-'.((int) $cell->column_index);
            $occupied[$key][$pos] = true;
        }

        $rows = [];
        foreach ($warehouses as $warehouse) {
            $warehouseDisplay = trim($warehouse->code.' - '.$warehouse->name, ' -');
            $warehouseId = (int) $warehouse->id;
            $positions = [];
            for ($row = 1; $row <= 20; $row++) {
                for ($col = 1; $col <= 20; $col++) {
                    $pos = $row.'-'.$col;
                    if (isset($occupied[$warehouseId][$pos])) {
                        continue;
                    }
                    $positions[] = sprintf('R%02d-C%02d', $row, $col);
                }
            }
            $rows[] = [
                'warehouse_code' => (string) $warehouse->code,
                'warehouse_display' => $warehouseDisplay,
                'positions' => $positions,
            ];
        }

        return $rows;
    }
}
