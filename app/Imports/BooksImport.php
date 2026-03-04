<?php

namespace App\Imports;

use App\Helpers\BookHelper;
use App\Helpers\FileHelpers;
use App\Models\Book;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class BooksImport
{

  protected const COLUMN_ALIASES = [
    'title'                 => ['tên sách', 'tensach', 'tên đề tài', 'ten sach', 'title', 'nhan đề', 'nhan de'],
    'authors'               => ['tác giả', 'tacgia', 'tac gia', 'author', 'authors', 'người viết'],
    'classification_code'   => ['mã phân loại', 'matheloai', 'ma the loai', 'phân loại', 'ma phan loai', 'classification', 'code', 'mã sách', 'ký hiệu phân loại'],
    'classification_detail' => ['phân loại chi tiết', 'machitiet', 'ma chi tiet', 'chi tiết phân loại', 'classification detail'],
    'category'              => ['danh mục', 'thể loại', 'theloai', 'category', 'loại sách', 'chủ đề'],
    'published_year'        => ['năm xuất bản', 'namxuatban', 'nam xuat ban', 'năm xb', 'nam xb', 'year', 'published year', 'năm'],
    'publication_place'     => ['nơi xuất bản', 'nơi xb', 'noi xb', 'publication place', 'nxb'],
    'publisher'             => ['nhà xuất bản', 'nhaxuatban', 'nha xuat ban', 'publisher'],
    'total_pages'           => ['số trang', 'so trang', 'pages', 'total pages', 'trang'],
    'book_size'             => ['khổ sách', 'kho sach', 'size', 'book size', 'kích thước'],
    'price'                 => ['giá', 'gia', 'giatien', 'gia tien', 'price', 'giá tiền', 'giá sách', 'đơn giá'],
    'notes'                 => ['ghi chú', 'ghichu', 'ghi chu', 'notes', 'note', 'chú thích'],
    'volume_number'         => ['tập số', 'tập', 'volume', 'vol', 'tập số'],
    'quantity'              => ['số lượng', 'soluong', 'so luong', 'quantity', 'số bản'],
    'warehouse_code'        => ['mã kho', 'makho', 'ma kho', 'kho sách'],
    'shelf'                 => ['vị trí kho', 'vitrikho', 'vi tri kho', 'shelf', 'kệ', 'ngăn'],
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

        // Thể loại: ưu tiên mã (MaTheLoai), không có thì dùng tên (Thể loại)
        $categoryId = null;
        $codeTheLoai = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['classification_code']);
        $nameTheLoai = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['category']);
        if ($codeTheLoai) {
          $category = Category::firstOrCreate(
            ['code' => $codeTheLoai],
            ['name' => $nameTheLoai ?: $codeTheLoai, 'code' => $codeTheLoai]
          );
          $categoryId = $category->id;
        } elseif ($nameTheLoai) {
          $slug = \Illuminate\Support\Str::slug($nameTheLoai);
          $code = $slug ?: 'DM-' . substr(uniqid(), -6);
          if (Category::where('code', $code)->exists()) {
            $code = 'DM-' . substr(md5($nameTheLoai . uniqid()), 0, 10);
          }
          $category = Category::firstOrCreate(
            ['name' => $nameTheLoai],
            ['name' => $nameTheLoai, 'code' => $code]
          );
          $categoryId = $category->id;
        }

        // Kho: MaKho -> warehouse_id
        $warehouseId = null;
        $maKho = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['warehouse_code']);
        if ($maKho) {
          $warehouse = Warehouse::firstOrCreate(
            ['code' => $maKho],
            ['name' => $maKho, 'code' => $maKho]
          );
          $warehouseId = $warehouse->id;
        }

        $shelf = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['shelf']);
        $params = $shelf ? ['shelf' => $shelf] : [];

        $publisherName = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['publisher']);
        $quantity = (int) (FileHelpers::parseNumber(
          FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['quantity'])
        ) ?? 0);
        if ($quantity < 0) {
          $quantity = 0;
        }

        $book = Book::create([
          'type'                  => 'book',
          'title'                 => $title,
          'classification_code'   => $codeTheLoai ?: FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['classification_code']),
          'classification_detail' => FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['classification_detail']),
          'category_id'           => $categoryId,
          'warehouse_id'          => $warehouseId,
          'publisher_name'        => $publisherName,
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
          'params'                => $params,
        ]);

        if ($quantity > 0) {
          BookHelper::createCopies($book, $quantity);
        }
        $book->updateStatistics();

        $authorNames = FileHelpers::getValueByAliases($row, self::COLUMN_ALIASES['authors']);
        if ($authorNames) {
          $names = preg_split('/[,;;&]+/', (string) $authorNames);
          $names = array_values(array_filter(array_map('trim', $names)));
          $main = $names[0] ?? null;
          $co = count($names) > 1 ? implode(', ', array_slice($names, 1)) : null;
          $book->update([
            'author' => $main,
            'co_authors' => $co,
          ]);
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

}
