<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FileHelpers
{
  /**
   * Các extension Excel được hỗ trợ.
   */
  public const EXCEL_EXTENSIONS = ['xlsx', 'xls', 'csv'];

  /**
   * Lấy extension của file.
   */
  public static function getFileExtension(UploadedFile $file): string
  {
    return strtolower($file->getClientOriginalExtension());
  }

  /**
   * Kiểm tra file có phải Excel không.
   */
  public static function isExcelFile(UploadedFile $file): bool
  {
    return in_array(self::getFileExtension($file), self::EXCEL_EXTENSIONS);
  }

  /**
   * Đọc file Excel thành mảng dữ liệu.
   * Dòng đầu tiên được dùng làm header.
   *
   * @param UploadedFile $file File upload
   * @param int $headerRow Dòng chứa header (mặc định: 1)
   * @param int|null $sheetIndex Index của sheet cần đọc (mặc định: 0 - sheet đầu tiên)
   * @return array{headers: string[], rows: array[], total_rows: int}
   */
  public static function readExcel(UploadedFile $file, int $headerRow = 1, ?int $sheetIndex = 0): array
  {
    $spreadsheet = IOFactory::load($file->getRealPath());
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

    // Excel serial number (numeric)
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
