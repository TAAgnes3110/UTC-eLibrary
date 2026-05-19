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
}
