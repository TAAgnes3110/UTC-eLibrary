<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class FileHelpers
{
  /**
   * Các extension Excel được hỗ trợ.
   */
  public const EXCEL_EXTENSIONS = ['xlsx', 'xls', 'csv'];

  /**
   * Lấy extension của file.
   *
   * @param UploadedFile|string $file
   * @return string
   */
  public static function getFileExtension(UploadedFile|string $file): string
  {
    if ($file instanceof UploadedFile) {
      return strtolower($file->getClientOriginalExtension());
    }
    return strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
  }

  /**
   * Kiểm tra file có phải Excel không.
   *
   * @param UploadedFile|string $file
   * @return bool
   */
  public static function isExcelFile(UploadedFile|string $file): bool
  {
    return in_array(self::getFileExtension($file), self::EXCEL_EXTENSIONS);
  }

  /**
   * Đọc file Excel thành mảng dữ liệu.
   * Dòng đầu tiên được dùng làm header.
   *
   * @param UploadedFile|string $file File upload hoặc đường dẫn
   * @param int $headerRow Dòng chứa header (mặc định: 1)
   * @param int|null $sheetIndex Index của sheet cần đọc (mặc định: 0 - sheet đầu tiên)
   * @return array{headers: string[], rows: array[], total_rows: int}
   */
  public static function readExcel(UploadedFile|string $file, int $headerRow = 1, ?int $sheetIndex = 0): array
  {
    $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getSheet($sheetIndex ?? 0);
    $data = $worksheet->toArray(null, true, true, true);

    if (empty($data) || !isset($data[$headerRow])) {
      return ['headers' => [], 'rows' => [], 'total_rows' => 0];
    }

    $rawHeaders = $data[$headerRow];
    $headers = self::normalizeHeaders($rawHeaders);

    $rows = [];
    foreach ($data as $rowIndex => $rowData) {
      if ($rowIndex <= $headerRow) {
        continue;
      }

      $values = array_values($rowData);
      if (self::isEmptyRow($values)) {
        continue;
      }

      $mapped = [];
      foreach ($headers as $colLetter => $headerName) {
        $mapped[$headerName] = isset($rowData[$colLetter]) ? trim((string) $rowData[$colLetter]) : null;
      }
      $mapped['_row_number'] = $rowIndex;
      $rows[] = $mapped;
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    return [
      'headers' => array_values($headers),
      'rows' => $rows,
      'total_rows' => count($rows),
    ];
  }

  /**
   * Xuất danh sách dữ liệu ra file Excel và tải về trực tiếp.
   *
   * @param array $data Dữ liệu cần xuất
   * @param string $filename Tên file (VD: export.xlsx)
   * @param array|null $headers Header của các cột (tùy chọn)
   * @return StreamedResponse
   */
  public static function downloadExcel(array $data, string $filename = 'export.xlsx', ?array $headers = null): StreamedResponse
  {
    $spreadsheet = self::createExcelExport($data, $headers);
    $ext = self::getFileExtension($filename);

    if ($ext === 'csv') {
      $writer = new Csv($spreadsheet);
      $contentType = 'text/csv';
    } else {
      $writer = new Xlsx($spreadsheet);
      $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, $filename, [
      'Content-Type' => $contentType,
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      'Cache-Control' => 'max-age=0',
    ]);
  }

  /**
   * Hỗ trợ lùi phiên bản cho hàm cũ (lưu vào ổ đĩa).
   */
  public static function exportExcel(array $data, string $filename, ?array $headers = null): void
  {
    self::saveExcel($data, $filename, $headers);
  }

  /**
   * Xuất danh sách dữ liệu ra file Excel và lưu vào server.
   *
   * @param array $data Dữ liệu cần xuất
   * @param string $path Đường dẫn file lưu
   * @param array|null $headers Header của các cột (tùy chọn)
   */
  public static function saveExcel(array $data, string $path, ?array $headers = null): void
  {
    $spreadsheet = self::createExcelExport($data, $headers);
    $ext = self::getFileExtension($path);

    if ($ext === 'csv') {
      $writer = new Csv($spreadsheet);
    } else {
      $writer = new Xlsx($spreadsheet);
    }

    $writer->save($path);
  }

  /**
   * Khởi tạo đối tượng Spreadsheet từ dữ liệu. Thêm định dạng sẵn (kẻ viền, nền, wrap, auto-size).
   *
   * @param array $data Dữ liệu cần xuất
   * @param array|null $headers Header của các cột (tùy chọn)
   */
  public static function createExcelExport(array $data, ?array $headers = null): Spreadsheet
  {
    $spreadsheet = new Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();
    $worksheet->setTitle('Sheet 1');

    $rowIndex = 1;

    // Nếu không truyền header riêng nhưng param list là array assoc
    if ($headers === null && !empty($data)) {
      $firstRow = reset($data);
      if (is_array($firstRow) && count(array_filter(array_keys($firstRow), 'is_string')) > 0) {
        $headers = array_keys($firstRow); // Tử động bóc tách từ Assoc Array Data
      }
    }

    // Thiết lập Header
    if (!empty($headers)) {
      $colIndex = 1;
      foreach ($headers as $header) {
        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
        $worksheet->setCellValue($colLetter . $rowIndex, is_string($header) ? $header : '');
        $colIndex++;
      }

      $lastColIndex = count($headers);
      $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);

      $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
          'allBorders' => ['borderStyle' => Border::BORDER_THIN],
        ],
        'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => ['argb' => 'FF4F81BD'], // Màu xanh dương standard
        ],
      ];
      $worksheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);
      $worksheet->getRowDimension(1)->setRowHeight(25);

      $rowIndex++;
    }

    // Ghi dữ liệu
    $lastColIndex = 1;
    if (!empty($data)) {
      $dataValues = [];
      foreach ($data as $row) {
        if (is_array($row)) {
          $dataValues[] = array_values($row);
        } else {
          $dataValues[] = (array) $row;
        }
      }

      $itemCountRow = !empty($dataValues[0]) ? count($dataValues[0]) : 1;
      $lastColIndex = max(!empty($headers) ? count($headers) : 1, $itemCountRow);

      // Từ dòng A2 trở đi...
      $worksheet->fromArray($dataValues, null, 'A' . $rowIndex, true);

      $lastCol = Coordinate::stringFromColumnIndex($lastColIndex);
      $lastRow = $rowIndex + count($dataValues) - 1;

      // Kẻ viền cho dữ liệu, alignment vertical center
      $dataStyle = [
        'borders' => [
          'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']],
        ],
        'alignment' => [
          'vertical' => Alignment::VERTICAL_CENTER,
          'wrapText' => true
        ],
      ];
      $worksheet->getStyle('A' . $rowIndex . ':' . $lastCol . $lastRow)->applyFromArray($dataStyle);
    } // empty($data) == false

    // Auto-size các cột để vừa nội dung và không dính chữ
    for ($i = 1; $i <= $lastColIndex; $i++) {
      $colText = Coordinate::stringFromColumnIndex($i);
      $worksheet->getColumnDimension($colText)->setAutoSize(true);
    }

    return $spreadsheet;
  }

  /**
   * Normalize headers: trim, lowercase, bỏ dấu cách thừa.
   *
   * @param array $rawHeaders
   * @return array<string, string> [colLetter => normalizedName]
   */
  public static function normalizeHeaders(array $rawHeaders): array
  {
    $headers = [];
    foreach ($rawHeaders as $colLetter => $value) {
      if ($value === null || trim((string) $value) === '') {
        continue;
      }
      $normalized = mb_strtolower(trim((string) $value));
      $normalized = preg_replace('/\s+/', ' ', $normalized);
      $headers[$colLetter] = $normalized;
    }
    return $headers;
  }

  /**
   * Kiểm tra xem dòng có trống hoàn toàn không.
   */
  public static function isEmptyRow(array $values): bool
  {
    foreach ($values as $val) {
      if ($val !== null && trim((string) $val) !== '') {
        return false;
      }
    }
    return true;
  }

  /**
   * Parse giá trị ngày tháng từ Excel.
   * Hỗ trợ: Excel serial number, Y-m-d, d/m/Y, d-m-Y.
   *
   * @param mixed $value
   * @return string|null Ngày dạng Y-m-d hoặc null
   */
  public static function parseDate(mixed $value): ?string
  {
    if (empty($value)) {
      return null;
    }
    if (is_numeric($value) && (int) $value > 30000) {
      try {
        $date = ExcelDate::excelToDateTimeObject((int) $value);
        return $date->format('Y-m-d');
      } catch (\Throwable) {
        return null;
      }
    }

    $value = trim((string) $value);
    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
    foreach ($formats as $format) {
      $date = \DateTime::createFromFormat($format, $value);
      if ($date && $date->format($format) === $value) {
        return $date->format('Y-m-d');
      }
    }

    return null;
  }

  /**
   * Parse giá trị số từ Excel (bỏ dấu phẩy, dấu chấm ngàn).
   *
   * @param mixed $value
   * @return float|null
   */
  public static function parseNumber(mixed $value): ?float
  {
    if ($value === null || trim((string) $value) === '') {
      return null;
    }

    $cleaned = preg_replace('/[^\d.,-]/', '', (string) $value);
    if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $cleaned)) {
      $cleaned = str_replace(['.', ','], '', $cleaned);
    } elseif (str_contains($cleaned, ',')) {
      $cleaned = str_replace(',', '.', $cleaned);
    }

    return is_numeric($cleaned) ? (float) $cleaned : null;
  }

  /**
   * Parse năm từ giá trị (có thể là số hoặc chuỗi).
   */
  public static function parseYear(mixed $value): ?int
  {
    if ($value === null || trim((string) $value) === '') {
      return null;
    }

    $year = (int) trim((string) $value);
    return ($year >= 1900 && $year <= 2100) ? $year : null;
  }

  /**
   * Tạo kết quả import chuẩn.
   *
   * @param int $success Số dòng thành công
   * @param int $skipped Số dòng bị bỏ qua (trùng lặp)
   * @param array $errors Danh sách lỗi [['row' => int, 'message' => string]]
   * @return array
   */
  public static function buildImportResult(int $success, int $skipped, array $errors): array
  {
    return [
      'status' => empty($errors) ? 'success' : (($success > 0) ? 'partial' : 'error'),
      'summary' => [
        'total_processed' => $success + $skipped + count($errors),
        'success' => $success,
        'skipped' => $skipped,
        'errors' => count($errors),
      ],
      'errors' => array_slice($errors, 0, 50),
    ];
  }

  /**
   * Tìm giá trị từ row theo danh sách alias.
   * Ví dụ: getValueByAliases($row, ['tên tác giả', 'tác giả', 'author', 'name'])
   *
   * @param array $row Dòng dữ liệu (đã map header)
   * @param array $aliases Danh sách tên cột có thể
   * @return string|null
   */
  public static function getValueByAliases(array $row, array $aliases): ?string
  {
    foreach ($aliases as $alias) {
      $alias = mb_strtolower(trim($alias));
      if (isset($row[$alias]) && trim((string) $row[$alias]) !== '') {
        return trim((string) $row[$alias]);
      }
    }
    return null;
  }
}
