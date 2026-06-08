<?php

namespace Tests\Feature\Backend;

use App\Helpers\FileHelpers;
use App\Imports\BookImport;
use App\Models\Book;
use App\Models\Classification;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

class BookImportTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    public function test_import_creates_book_from_excel_with_template_headers(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-01',
            'name' => 'Khoa học máy tính',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'KHO-GT',
            'name' => 'Kho giáo trình',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classification = Classification::query()->findOrFail($classificationId);
        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        $headers = [
            'Phân loại sách (*)',
            'Tên sách (*)',
            'Kho sách (*)',
            'Số lượng (*)',
            'Tác giả (ngăn cách bằng dấu , hoặc ;)',
            'Loại sách (0: giáo trình, 1: tham khảo, 2: tài liệu số)',
        ];
        $rows = [[
            "{$classification->code} - {$classification->name}",
            'Sách nhập Excel kiểm tra',
            $warehouse->code,
            '2',
            'Nguyễn Văn A; Trần Thị B',
            '0',
        ]];

        $spreadsheet = FileHelpers::createWorkbook([
            ['title' => 'Sheet1_Sach', 'headers' => $headers, 'rows' => $rows],
        ]);
        $path = storage_path('framework/testing/book-import-verify.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-verify.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = BookImport::import($file);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['summary']['success']);

        $book = Book::query()->where('title', 'Sách nhập Excel kiểm tra')->first();
        $this->assertNotNull($book);
        $this->assertSame(2, (int) $book->quantity);
        $this->assertSame($warehouse->id, (int) $book->warehouse_id);
        $this->assertSame($classification->id, (int) $book->classification_id);
        $this->assertCount(2, $book->authors);

        $this->assertDatabaseHas('storage_cabinets', [
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
        ]);
    }

    public function test_import_api_accepts_valid_xlsx(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-API',
            'name' => 'API import',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'KHO-API',
            'name' => 'Kho API',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classification = Classification::query()->findOrFail($classificationId);
        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        $spreadsheet = FileHelpers::createWorkbook([
            [
                'title' => 'Sheet1_Sach',
                'headers' => ['Phân loại sách (*)', 'Tên sách (*)', 'Kho sách (*)', 'Số lượng (*)'],
                'rows' => [[
                    "{$classification->code} - {$classification->name}",
                    'Sách qua API import',
                    $warehouse->code,
                    '1',
                ]],
            ],
        ]);
        $path = storage_path('framework/testing/book-import-api.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-api.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        [, $headers] = $this->adminContext();

        $this->post('/api/v1/books/import', ['file' => $file], $headers)
            ->assertSuccessful()
            ->assertJsonPath('data.summary.success', 1);

        $this->assertDatabaseHas('books', ['title' => 'Sách qua API import']);
    }

    public function test_import_uses_warehouse_code_from_excel_when_provided(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-WH1',
            'name' => 'Kho theo mã excel',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('warehouses')->insertGetId([
            'code' => 'KHO-EXCEL',
            'name' => 'Kho nhập từ mã excel',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classification = Classification::query()->findOrFail($classificationId);

        $spreadsheet = FileHelpers::createWorkbook([
            [
                'title' => 'Sheet1_Sach',
                'headers' => ['Phân loại sách (*)', 'Tên sách (*)', 'Kho sách (*)', 'Số lượng (*)', 'Mã sách'],
                'rows' => [[
                    "{$classification->code} - {$classification->name}",
                    'Sách dùng kho từ excel',
                    'KHO-EXCEL',
                    '1',
                    'MS-EXCEL-001',
                ]],
            ],
        ]);
        $path = storage_path('framework/testing/book-import-wh-code.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-wh-code.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = BookImport::import($file);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['summary']['success']);
        $this->assertDatabaseHas('books', [
            'title' => 'Sách dùng kho từ excel',
            'book_code' => 'MS-EXCEL-001',
        ]);
        $this->assertDatabaseHas('warehouses', ['code' => 'KHO-EXCEL']);
    }

    public function test_import_auto_creates_warehouse_when_warehouse_code_is_missing(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-WH2',
            'name' => 'Kho auto',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classification = Classification::query()->findOrFail($classificationId);

        $spreadsheet = FileHelpers::createWorkbook([
            [
                'title' => 'Sheet1_Sach',
                'headers' => ['Phân loại sách (*)', 'Tên sách (*)', 'Kho sách (*)', 'Số lượng (*)'],
                'rows' => [[
                    "{$classification->code} - {$classification->name}",
                    'Sách kho tự tạo',
                    '',
                    '2',
                ]],
            ],
        ]);
        $path = storage_path('framework/testing/book-import-auto-wh.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-auto-wh.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = BookImport::import($file);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['summary']['success']);
        $book = Book::query()->where('title', 'Sách kho tự tạo')->first();
        $this->assertNotNull($book);
        $this->assertNotNull($book->warehouse_id);
        $this->assertDatabaseHas('warehouses', [
            'id' => $book->warehouse_id,
            'is_active' => 1,
        ]);
    }

    public function test_import_skips_template_hint_row_and_reads_multiline_headers(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-TPL',
            'name' => 'Phân loại mẫu',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('warehouses')->insertGetId([
            'code' => 'KHO-GT',
            'name' => 'Kho giáo trình',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classification = Classification::query()->findOrFail($classificationId);

        $spreadsheet = FileHelpers::createWorkbook([
            [
                'title' => 'Sheet0_HuongDan',
                'headers' => ['Mục', 'Nội dung'],
                'rows' => [['Hướng dẫn', 'Chỉ nhập giáo trình và tham khảo']],
            ],
            [
                'title' => 'Sheet1_Sach',
                'headers' => [
                    "Số đăng ký cá biệt\n(Để trống = hệ thống tự sinh)",
                    "Phân loại sách (*)\nNhập mã hoặc tên (xem sheet 2)",
                    "Tên sách (*)\nBắt buộc khi thêm mới",
                    "Loại sách\n0: Giáo trình, 1: Tham khảo",
                    "Kho sách\nCó thể để trống",
                    "Số lượng (*)\nPhải > 0",
                ],
                'rows' => [
                    [
                        '',
                        '',
                        '',
                        '',
                        'Có thể để trống để hệ thống tự tạo kho.',
                        '',
                    ],
                    [
                        '',
                        $classification->code,
                        'Sách từ file mẫu cũ',
                        '0',
                        'KHO-GT',
                        '5',
                    ],
                ],
            ],
            [
                'title' => 'Sheet2_PhanLoaiSach',
                'headers' => ['Mã', 'Tên'],
                'rows' => [[$classification->code, $classification->name]],
            ],
        ]);
        $path = storage_path('framework/testing/book-import-template-layout.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-template-layout.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = BookImport::import($file);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['summary']['success']);
        $this->assertDatabaseHas('books', [
            'title' => 'Sách từ file mẫu cũ',
            'quantity' => 5,
            'resource_type' => 'textbook',
        ]);
    }

    public function test_import_rejects_digital_resource_type(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-DIG',
            'name' => 'Phân loại digital',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('warehouses')->insertGetId([
            'code' => 'KHO-GT',
            'name' => 'Kho giáo trình',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classification = Classification::query()->findOrFail($classificationId);

        $spreadsheet = FileHelpers::createWorkbook([
            [
                'title' => 'Sheet1_Sach',
                'headers' => ['Phân loại sách (*)', 'Tên sách (*)', 'Kho sách (*)', 'Số lượng (*)', 'Loại sách'],
                'rows' => [[
                    $classification->code,
                    'Sách tài liệu số',
                    'KHO-GT',
                    '1',
                    '2',
                ]],
            ],
        ]);
        $path = storage_path('framework/testing/book-import-digital.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-digital.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = BookImport::import($file);

        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('Giáo trình', $result['errors'][0]['message'] ?? '');
        $this->assertDatabaseMissing('books', ['title' => 'Sách tài liệu số']);
    }

    public function test_import_rolls_back_all_rows_when_any_row_fails(): void
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'PL-ROLLBACK',
            'name' => 'Phân loại rollback',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('warehouses')->insertGetId([
            'code' => 'KHO-ROLLBACK',
            'name' => 'Kho rollback',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classification = Classification::query()->findOrFail($classificationId);

        $spreadsheet = FileHelpers::createWorkbook([
            [
                'title' => 'Sheet1_Sach',
                'headers' => ['Phân loại sách (*)', 'Tên sách (*)', 'Kho sách (*)', 'Số lượng (*)'],
                'rows' => [
                    [
                        "{$classification->code} - {$classification->name}",
                        'Sách hợp lệ nhưng phải rollback',
                        'KHO-ROLLBACK',
                        '1',
                    ],
                    [
                        "{$classification->code} - {$classification->name}",
                        'Sách gây lỗi',
                        'KHO-ROLLBACK',
                        '0',
                    ],
                ],
            ],
        ]);
        $path = storage_path('framework/testing/book-import-rollback.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'book-import-rollback.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $result = BookImport::import($file);

        $this->assertSame('error', $result['status']);
        $this->assertSame(0, $result['summary']['success']);
        $this->assertGreaterThan(0, $result['summary']['errors']);
        $this->assertDatabaseMissing('books', ['title' => 'Sách hợp lệ nhưng phải rollback']);
        $this->assertDatabaseMissing('books', ['title' => 'Sách gây lỗi']);
    }
}
