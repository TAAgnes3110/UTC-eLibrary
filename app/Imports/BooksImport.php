<?php

namespace App\Imports;

use App\Helpers\FileHelpers;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BooksImport
{
  /**
   * Các alias cho tên cột (hỗ trợ cả tiếng Việt và tiếng Anh).
   */
  protected const COLUMN_ALIASES = [
    'title'               => ['tên sách', 'tên đề tài', 'ten sach', 'title', 'nhan đề', 'nhan de'],
    'authors'             => ['tác giả', 'tac gia', 'author', 'authors', 'người viết'],
    'classification_code' => ['mã phân loại', 'phân loại', 'ma phan loai', 'classification', 'code', 'mã sách', 'ký hiệu phân loại'],
    'classification_detail' => ['phân loại chi tiết', 'chi tiết phân loại', 'classification detail'],
    'category'            => ['danh mục', 'thể loại', 'category', 'loại sách', 'chủ đề'],
    'published_year'      => ['năm xuất bản', 'năm xb', 'nam xb', 'year', 'published year', 'năm'],
    'publication_place'   => ['nơi xuất bản', 'nơi xb', 'noi xb', 'publication place', 'nxb'],
    'total_pages'         => ['số trang', 'so trang', 'pages', 'total pages', 'trang'],
    'book_size'           => ['khổ sách', 'kho sach', 'size', 'book size', 'kích thước'],
    'price'               => ['giá', 'gia', 'price', 'giá tiền', 'giá sách', 'đơn giá'],
    'notes'               => ['ghi chú', 'ghi chu', 'notes', 'note', 'chú thích'],
    'volume_number'       => ['tập số', 'tập', 'volume', 'vol', 'tập số'],
  ];

  /**
   * Import sách từ file Excel.
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
        $title = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['title']);

        if (empty($title)) {
          $errors[] = ['row' => $rowNum, 'message' => 'Thiếu tên sách.'];
          continue;
        }

        DB::beginTransaction();

        // Tìm hoặc tạo Category
        $categoryId = null;
        $categoryName = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['category']);
        if ($categoryName) {
          $category = Category::firstOrCreate(
            ['name' => $categoryName],
            ['name' => $categoryName]
          );
          $categoryId = $category->id;
        }

        // Tạo sách
        $book = Book::create([
          'title'                 => $title,
          'classification_code'   => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['classification_code']),
          'classification_detail' => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['classification_detail']),
          'category_id'           => $categoryId,
          'publication_place'     => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['publication_place']),
          'published_year'        => FileHelpers::parseYear(
            FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['published_year'])
          ),
          'total_pages'           => FileHelpers::parseNumber(
            FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['total_pages'])
          ),
          'book_size'             => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['book_size']),
          'volume_number'         => FileHelpers::parseNumber(
            FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['volume_number'])
          ),
          'price'                 => FileHelpers::parseNumber(
            FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['price'])
          ),
          'notes'                 => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['notes']),
          'status'                => 'available',
        ]);

        // Xử lý tác giả (có thể nhiều tác giả, cách nhau bởi dấu phẩy hoặc dấu chấm phẩy)
        $authorNames = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['authors']);
        if ($authorNames) {
          $this->attachAuthors($book, $authorNames);
        }

        DB::commit();
        $success++;
      } catch (\Throwable $e) {
        DB::rollBack();
        $errors[] = ['row' => $rowNum, 'message' => 'Lỗi: ' . $e->getMessage()];
      }
    }

    return FileHelpers::buildImportResult($success, $skipped, $errors);
  }

  /**
   * Tách danh sách tác giả và liên kết vào sách.
   * Tự động tạo Author mới nếu chưa tồn tại.
   */
  protected function attachAuthors(Book $book, string $authorNames): void
  {
    // Tách theo dấu phẩy, chấm phẩy, hoặc dấu "&"
    $names = preg_split('/[,;;&]+/', $authorNames);
    $order = 0;

    foreach ($names as $name) {
      $name = trim($name);
      if (empty($name)) {
        continue;
      }

      $author = Author::firstOrCreate(
        ['name' => $name],
        ['name' => $name]
      );

      $book->authors()->attach($author->id, [
        'role'  => $order === 0 ? 'author' : 'co-author',
        'order' => $order,
      ]);

      $order++;
    }
  }
}
