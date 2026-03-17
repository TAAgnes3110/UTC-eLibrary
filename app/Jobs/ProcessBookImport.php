<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Helpers\FileHelpers;
use App\Models\Author;
use App\Models\Book;
use App\Models\ClassificationDetail;
use App\Models\Import;
use App\Models\Publisher;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessBookImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Import $import
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $this->import->update([
            'status' => ImportStatus::PROCESSING,
            'started_at' => now(),
        ]);
        $filePath = storage_path('app/' . $this->import->file_path);
        $result = FileHelpers::readExcel($filePath, 1, 0);
        $rows = $result['rows'];
        $success = 0;
        $skipped = 0;
        $errors = [];
        $chunkSize = 500;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::transaction(function () use ($chunk, &$success, &$skipped, &$errors) {
                foreach ($chunk as $row) {
                    try {
                        $registrationNumber = FileHelpers::getValueByAliases($row, [
                            'số đăng ký cá biệt',
                            'so dk ca biet',
                            'so_dk_ca_biet',
                            'registration_number',
                        ]);
                        $classificationDetailCode = FileHelpers::getValueByAliases($row, [
                            'mã phân loại chi tiết',
                            'ma phan loai chi tiet',
                            'classification_detail_code',
                        ]);
                        $bookCode = FileHelpers::getValueByAliases($row, [
                            'mã sách',
                            'ma sach',
                            'book_code',
                        ]);
                        $title = FileHelpers::getValueByAliases($row, [
                            'nhan đề',
                            'nhan de',
                            'tên sách',
                            'ten sach',
                            'title',
                        ]);
                        $authorsRaw = FileHelpers::getValueByAliases($row, [
                            'tác giả',
                            'tac gia',
                            'authors',
                        ]);
                        $publishersRaw = FileHelpers::getValueByAliases($row, [
                            'nhà xuất bản',
                            'nha xuat ban',
                            'publishers',
                        ]);
                        $warehouseCode = FileHelpers::getValueByAliases($row, [
                            'kho sách',
                            'kho sach',
                            'mã kho',
                            'ma kho',
                            'warehouse_code',
                        ]);
                        if (!$title || !$warehouseCode) {
                            $skipped++;
                            continue;
                        }
                        $classificationDetail = null;
                        if ($classificationDetailCode) {
                            $classificationDetail = ClassificationDetail::query()
                                ->where('code', $classificationDetailCode)
                                ->first();
                        }
                        $warehouse = Warehouse::query()->where('code', $warehouseCode)->first();
                        if (!$warehouse) {
                            $skipped++;
                            continue;
                        }
                        $publishedYear = FileHelpers::parseYear(
                            FileHelpers::getValueByAliases($row, ['năm xuất bản', 'nam xuat ban', 'published_year'])
                        );
                        $pages = FileHelpers::parseNumber(
                            FileHelpers::getValueByAliases($row, ['số trang', 'so trang', 'pages'])
                        );
                        $bookSize = FileHelpers::getValueByAliases($row, ['khổ sách', 'kho sach', 'book_size']);
                        $price = FileHelpers::parseNumber(
                            FileHelpers::getValueByAliases($row, ['giá tiền', 'gia tien', 'price'])
                        );
                        $quantity = FileHelpers::parseNumber(
                            FileHelpers::getValueByAliases($row, ['số lượng', 'so luong', 'quantity'])
                        ) ?? 0;
                        $book = null;
                        if ($registrationNumber) {
                            $book = Book::query()->where('registration_number', $registrationNumber)->first();
                        }
                        if (!$book && $bookCode) {
                            $book = Book::query()->where('book_code', $bookCode)->first();
                        }
                        $payload = [
                            'registration_number' => $registrationNumber,
                            'book_code' => $bookCode,
                            'title' => $title,
                            'published_year' => $publishedYear,
                            'pages' => $pages,
                            'book_size' => $bookSize,
                            'price' => $price,
                            'quantity' => (int) $quantity,
                            'classification_detail_id' => $classificationDetail?->id,
                            'classification_id' => $classificationDetail?->classification_id,
                            'warehouse_id' => $warehouse->id,
                        ];
                        if ($book) {
                            $book->fill($payload);
                            $book->save();
                        } else {
                            $book = Book::create($payload);
                        }
                        if ($authorsRaw) {
                            $authorNames = array_filter(array_map('trim', preg_split('/[;,]/', $authorsRaw)));
                            $authorIds = [];
                            $order = 0;
                            foreach ($authorNames as $name) {
                                $slug = Str::slug($name);
                                $author = Author::firstOrCreate(
                                    ['slug' => $slug],
                                    ['name' => $name, 'params' => []]
                                );
                                $authorIds[$author->id] = ['order' => $order++];
                            }
                            if ($authorIds) {
                                $book->authors()->syncWithoutDetaching($authorIds);
                            }
                        }
                        if ($publishersRaw) {
                            $publisherNames = array_filter(array_map('trim', preg_split('/[;,]/', $publishersRaw)));
                            $publisherIds = [];
                            $order = 0;
                            foreach ($publisherNames as $name) {
                                $slug = Str::slug($name);
                                $publisher = Publisher::firstOrCreate(
                                    ['slug' => $slug],
                                    ['name' => $name, 'params' => []]
                                );
                                $publisherIds[$publisher->id] = ['order' => $order++];
                            }
                            if ($publisherIds) {
                                $book->publishers()->syncWithoutDetaching($publisherIds);
                            }
                        }
                        $success++;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'row' => $row['_row_number'] ?? null,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            });
            $this->import->update([
                'processed_rows' => $success + $skipped + count($errors),
                'success_rows' => $success,
                'skipped_rows' => $skipped,
                'error_rows' => count($errors),
            ]);
        }
        $status = empty($errors) ? ImportStatus::COMPLETED : ($success > 0 ? ImportStatus::PARTIAL : ImportStatus::FAILED);
        $this->import->update([
            'status' => $status,
            'finished_at' => now(),
            'meta' => [
                'errors' => array_slice($errors, 0, 50),
            ],
        ]);
    }
}

