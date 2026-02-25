<?php

namespace App\Imports;

use App\Helpers\FileHelpers;
use App\Models\Author;
use Illuminate\Http\UploadedFile;

class AuthorsImport
{
  /**
   * Các alias cho tên cột (hỗ trợ cả tiếng Việt và tiếng Anh).
   */
  protected const COLUMN_ALIASES = [
    'name'       => ['tên tác giả', 'tên', 'tác giả', 'author', 'name', 'họ tên', 'ho ten'],
    'tieu_su'    => ['tiểu sử', 'tieu su', 'biography', 'bio', 'giới thiệu'],
    'birth_date' => ['ngày sinh', 'ngay sinh', 'birth_date', 'birthday', 'sinh ngày', 'năm sinh'],
  ];

  /**
   * Import tác giả từ file Excel.
   *
   * @param UploadedFile $file
   * @return array Kết quả import
   */
  public function import(UploadedFile $file): array
  {
    $data = FileHelpers::readExcel($file);

    if (empty($data['rows'])) {
      return FileHelpers::buildImportResult(0, 0, [
        ['row' => 0, 'message' => 'File không có dữ liệu hoặc không đúng định dạng.']
      ]);
    }

    $success = 0;
    $skipped = 0;
    $errors = [];

    foreach ($data['rows'] as $row) {
      $rowNum = $row['_row_number'] ?? '?';

      try {
        $name = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['name']);

        if (empty($name)) {
          $errors[] = ['row' => $rowNum, 'message' => 'Thiếu tên tác giả.'];
          continue;
        }

        // Kiểm tra trùng tên
        $exists = Author::where('name', $name)->exists();
        if ($exists) {
          $skipped++;
          continue;
        }

        Author::create([
          'name'       => $name,
          'tieu_su'    => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['tieu_su']),
          'birth_date' => FileHelpers::parseDate(
            FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['birth_date'])
          ),
        ]);

        $success++;
      } catch (\Throwable $e) {
        $errors[] = ['row' => $rowNum, 'message' => 'Lỗi: ' . $e->getMessage()];
      }
    }

    return FileHelpers::buildImportResult($success, $skipped, $errors);
  }
}
