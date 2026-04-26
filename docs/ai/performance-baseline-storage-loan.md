# Baseline hieu nang - Loan + Storage

Ngay do: 2026-04-22

## Pham vi do
- Test backend luong muon/tra co dong bo so luong theo tu sach.
- Test API module tu sach sau khi bo logic suc chua.

Lenh do:

`php artisan test tests/Feature/Backend/LoanStorageQuantitySyncTest.php tests/Feature/Backend/StorageCabinetApiTest.php --profile`

## Ket qua
- Tong thoi gian: ~1.95s
- 8 tests, 44 assertions, 100% pass
- Test ton thoi gian cao nhat:
  - `LoanStorageQuantitySyncTest::test_create_loan_decrements_cabinet_current_quantity` ~1.09s

## Diem nong da ghi nhan
- Dong bo `current_quantity` theo `storage_cabinets` la duong critical trong transaction muon/tra.
- Truy van dem `book_copies` theo `warehouse_id + status + deleted_at` can toi uu index.

## Toi uu da ap dung trong dot nay
- Tach service dong bo so luong: `StorageQuantitySyncService`.
- Chuyen sang dong bo theo cabinet bi anh huong, uu tien thao tac cap nhat co pham vi ro rang.
- Bo sung command van hanh: `php artisan storage:sync-quantities`.
