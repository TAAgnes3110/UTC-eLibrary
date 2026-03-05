# Hướng dẫn chạy test

## Yêu cầu

- PHP có extension **PDO SQLite** (mặc định dùng SQLite in-memory)
- Bật trong `php.ini`:
  ```ini
  extension=pdo_sqlite
  extension=sqlite3
  ```
- **Nếu không có SQLite:** bootstrap tự chuyển sang MySQL. Tạo DB test trước:
  ```sql
  CREATE DATABASE elibrary_test;
  ```
  (Hoặc `{DB_DATABASE từ .env}_test` nếu khác)

## Chạy test

```bash
composer test
# hoặc
php artisan test
```

## Chạy theo nhóm

```bash
# Chỉ Unit
php artisan test --testsuite=Unit

# Chỉ Feature
php artisan test --testsuite=Feature

# Một file cụ thể
php artisan test tests/Feature/Backend/ApiRoutesTest.php
```

## Cấu trúc test

| File | Mô tả |
|------|-------|
| `Feature/Backend/ApiRoutesTest.php` | Health, refresh 401, protected routes 401 |
| `Feature/Backend/AdminApiTest.php` | Users, books, faculties, categories, roles, profile-change-requests (admin) |
| `Feature/Backend/AuthApiTest.php` | Register, verify OTP, login, logout |
| `Feature/Backend/ReaderApiTest.php` | /me/dashboard, loans, card, profile |
| `Feature/Backend/MasterDataApiTest.php` | /master-data |
| `Feature/Backend/ActsAsApiUser.php` | Trait tạo user + JWT token |

## PHPDoc

Các test dùng chuẩn PHPDoc: `@param`, `@return`, `@see` cho dễ đọc và IDE hỗ trợ.
