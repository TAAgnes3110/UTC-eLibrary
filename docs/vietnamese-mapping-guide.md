# Hướng dẫn sử dụng tên tiếng Việt trong Code

Hệ thống đã được tối ưu để hỗ trợ gọi tên thuộc tính bằng tiếng Việt, giúp bạn dễ dàng code mà không cần nhớ tên tiếng Anh chuẩn của Database.

## Ví dụ sử dụng

### 1. Với Sách (Book)
Thay vì dùng `title`, `price`... bạn có thể dùng:
```php
$sach = Book::create([
    'ten_sach' => 'Lập trình Laravel',
    'gia' => 200000,
    'tac_gia' => 'Nguyen Van A' // Cần xử lý relation riêng, nhưng mapping field cơ bản ok
]);

echo $sach->ten_sach; // Output: Lập trình Laravel
echo $sach->gia;      // Output: 200.000 (định dạng số)
```

### 2. Với Độc giả (Reader)
```php
$docGia = Reader::find(1);
echo $docGia->ho_ten;       // tương đương $docGia->full_name
echo $docGia->so_dien_thoai; // tương đương $docGia->phone
```

### 3. Với Mượn trả (Loan)
```php
$phieu = Loan::create([
    'doc_gia_id' => 1,
    'ngay_muon' => now(),
    'ngay_hen_tra' => now()->addDays(7),
    'trang_thai' => 'active'
]);
```

## Danh sách Mapping đã hỗ trợ

### Book (Sách)
- `ten_sach`, `tieu_de` -> `title`
- `gia` -> `price`
- `so_trang` -> `total_pages`
- `nam_xuat_ban` -> `published_year`
- `noi_xuat_ban` -> `publication_place`
- `ngon_ngu` -> `language`
- `tom_tat` -> `summary`
- ...

### Reader (Độc giả)
- `ho_ten` -> `full_name`
- `ma_doc_gia` -> `reader_code`
- `so_dien_thoai` -> `phone`
- `dia_chi` -> `address`
- `ngay_sinh` -> `birth_date`
- ...

### Loan (Mượn trả)
- `ngay_muon` -> `loan_date`
- `ngay_hen_tra` -> `due_date`
- `ngay_tra` -> `return_date`
- ...

### Lưu ý
- Database thực tế vẫn dùng tên tiếng Anh (`title`, `loan_date`...).
- Việc này chỉ giúp bạn thao tác trong Code PHP/Laravel dễ dàng hơn.
- Khi viết câu truy vấn Raw SQL (`DB::select`), bạn vẫn phải dùng tên tiếng Anh.
