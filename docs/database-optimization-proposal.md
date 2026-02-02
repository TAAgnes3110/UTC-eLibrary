# Đề xuất Tối ưu hóa Database cho UTC-eLibrary

## 📋 Tổng quan

Document này phân tích và đề xuất tối ưu hóa cấu trúc database cho hệ thống UTC-eLibrary dựa trên:
- Cấu trúc database hiện tại của UTC-eLibrary
- Tham khảo từ dự án QuanLyThuVien-laravel
- Best practices cho hệ thống quản lý thư viện

---

## 🔍 So sánh cấu trúc hiện tại

### QuanLyThuVien-laravel (Tham khảo)
```
Tables:
├── danh_muc (categories)
├── tac_gia (authors)
├── nha_xuat_ban (publishers)
├── sach (books)
├── doc_gia (readers)
├── phieu_muon (loan slips)
└── chi_tiet_phieu_muon (loan details)
```

### UTC-eLibrary (Hiện tại)
```
Tables:
├── categories (có parent_id - hỗ trợ phân cấp)
├── authors (chi tiết hơn: pen_name, nationality, biography)
├── publishers (chi tiết hơn: code, country, contact_person)
├── books (chi tiết hơn: classification, volume, params)
├── book_author (many-to-many với role)
├── book_copies (quản lý từng bản sao vật lý)
├── loans (mượn trả)
├── fines (phạt)
├── reservations (đặt trước)
└── users (kết hợp với Spatie Permissions)
```

---

## ✅ Điểm mạnh của UTC-eLibrary

### 1. **Tách biệt Books và Book Copies**
- ✅ **Books**: Thông tin về đầu sách (metadata)
- ✅ **Book Copies**: Từng bản sao vật lý với barcode, location, condition
- 👍 Phù hợp với thư viện thực tế (1 đầu sách có nhiều bản)

### 2. **Quan hệ Many-to-Many cho Authors**
- ✅ Một sách có nhiều tác giả
- ✅ Phân biệt role: author, co-author, editor, translator
- ✅ Có thứ tự (order) cho tác giả chính/phụ

### 3. **Hệ thống Fines riêng biệt**
- ✅ Quản lý phạt độc lập
- ✅ Nhiều loại phạt: overdue, lost, damaged
- ✅ Tracking payment method và processed_by

### 4. **Reservations System**
- ✅ Cho phép đặt trước sách
- ✅ Priority queue system
- ✅ Expiry date cho reservation

### 5. **Soft Deletes**
- ✅ Books, Book Copies có soft deletes
- 👍 Bảo toàn dữ liệu lịch sử

---

## 🔧 Đề xuất Tối ưu hóa

### 1. **Thêm bảng Readers/Library Cards** ⭐ QUAN TRỌNG

Hiện tại UTC-eLibrary chỉ dùng `users` table. Nên tách riêng thông tin độc giả:

```php
// Migration: create_readers_table.php
Schema::create('readers', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id')->nullable();
    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

    // Thông tin cá nhân
    $table->string('reader_code')->unique()->comment('Mã độc giả');
    $table->string('full_name')->comment('Họ tên');
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->date('birth_date')->nullable();
    $table->text('address')->nullable();

    // Thông tin sinh viên/giảng viên
    $table->string('student_code')->nullable()->comment('Mã sinh viên');
    $table->string('employee_code')->nullable()->comment('Mã cán bộ');
    $table->unsignedInteger('faculty_id')->nullable();
    $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null');
    $table->unsignedInteger('department_id')->nullable();
    $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

    // Loại độc giả
    $table->enum('reader_type', ['student', 'teacher', 'staff', 'guest'])->default('student');

    // Thẻ thư viện
    $table->string('card_number')->unique()->nullable()->comment('Số thẻ thư viện');
    $table->date('card_issued_date')->nullable();
    $table->date('card_expiry_date')->nullable();
    $table->enum('card_status', ['active', 'expired', 'suspended', 'revoked'])->default('active');

    // Giới hạn mượn
    $table->integer('max_books')->default(5)->comment('Số sách tối đa được mượn');
    $table->integer('max_days')->default(30)->comment('Số ngày mượn tối đa');

    // Trạng thái
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();

    $table->timestamps();
    $table->softDeletes();
});
```

**Lý do:**
- Tách biệt authentication (users) và library management (readers)
- Một user có thể không phải là reader
- Quản lý thẻ thư viện riêng biệt
- Phân loại độc giả theo vai trò (sinh viên, giảng viên, khách)

---

### 2. **Cải tiến Loans Table**

Thêm các trường cần thiết:

```php
// Thêm vào loans table
$table->unsignedInteger('reader_id')->after('id');
$table->foreign('reader_id')->references('id')->on('readers')->onDelete('cascade');

// Thêm điều kiện sách khi mượn/trả
$table->enum('condition_on_loan', ['new', 'good', 'fair', 'poor'])->nullable()->after('book_copy_id');
$table->enum('condition_on_return', ['new', 'good', 'fair', 'poor'])->nullable()->after('condition_on_loan');

// Thêm auto-calculate overdue
$table->integer('overdue_days')->default(0)->comment('Số ngày quá hạn');
$table->decimal('overdue_fine', 10, 2)->default(0)->comment('Tiền phạt quá hạn');

// Thêm renewal tracking
$table->integer('max_renewals')->default(2)->comment('Số lần gia hạn tối đa');
$table->date('last_renewal_date')->nullable();
```

---

### 3. **Thêm bảng Loan History** ⭐ QUAN TRỌNG

Tracking mọi thay đổi của loan:

```php
Schema::create('loan_histories', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('loan_id');
    $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');

    $table->enum('action', [
        'created',
        'renewed',
        'returned',
        'overdue',
        'lost',
        'damaged',
        'cancelled'
    ])->comment('Hành động');

    $table->unsignedInteger('performed_by')->nullable();
    $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');

    $table->text('notes')->nullable();
    $table->json('metadata')->nullable()->comment('Dữ liệu bổ sung');

    $table->timestamp('performed_at')->useCurrent();
});
```

---

### 4. **Cải tiến Books Table**

Thêm các trường từ Excel và best practices:

```php
// Thêm vào books table
$table->string('isbn')->nullable()->unique()->after('title');
$table->string('isbn13')->nullable()->after('isbn');

// Thông tin vật lý
$table->string('dimensions')->nullable()->comment('Kích thước (cm)');
$table->string('weight')->nullable()->comment('Trọng lượng');
$table->string('language')->default('vi')->comment('Ngôn ngữ');

// Thông tin xuất bản
$table->string('edition')->nullable()->comment('Lần tái bản');
$table->string('series')->nullable()->comment('Bộ sách');

// Thống kê
$table->integer('total_copies')->default(0)->comment('Tổng số bản');
$table->integer('available_copies')->default(0)->comment('Số bản có sẵn');
$table->integer('loan_count')->default(0)->comment('Số lần được mượn');
$table->integer('reservation_count')->default(0)->comment('Số lần được đặt');
$table->decimal('average_rating', 3, 2)->nullable()->comment('Đánh giá trung bình');

// SEO & Search
$table->text('keywords')->nullable()->comment('Từ khóa tìm kiếm');
$table->text('summary')->nullable()->comment('Tóm tắt ngắn');
$table->string('cover_image')->nullable()->comment('Ảnh bìa');
$table->string('thumbnail')->nullable()->comment('Ảnh thumbnail');

// Indexes để tối ưu search
$table->index(['title', 'isbn']);
$table->index('published_year');
$table->index('status');
$table->fullText(['title', 'keywords', 'summary']); // MySQL 5.7+
```

---

### 5. **Thêm bảng Book Reviews/Ratings** (Optional nhưng nên có)

```php
Schema::create('book_reviews', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('book_id');
    $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
    $table->unsignedInteger('user_id');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

    $table->integer('rating')->comment('1-5 sao');
    $table->text('review')->nullable();
    $table->boolean('is_verified')->default(false)->comment('Đã mượn sách này');
    $table->boolean('is_approved')->default(false);

    $table->timestamps();

    $table->unique(['book_id', 'user_id']); // Mỗi user chỉ review 1 lần
});
```

---

### 6. **Thêm bảng Statistics/Reports Cache**

Để tối ưu performance cho dashboard:

```php
Schema::create('library_statistics', function (Blueprint $table) {
    $table->increments('id');
    $table->date('stat_date')->unique();

    // Thống kê sách
    $table->integer('total_books')->default(0);
    $table->integer('total_copies')->default(0);
    $table->integer('available_copies')->default(0);
    $table->integer('borrowed_copies')->default(0);

    // Thống kê mượn trả
    $table->integer('loans_today')->default(0);
    $table->integer('returns_today')->default(0);
    $table->integer('active_loans')->default(0);
    $table->integer('overdue_loans')->default(0);

    // Thống kê độc giả
    $table->integer('total_readers')->default(0);
    $table->integer('active_readers')->default(0);
    $table->integer('new_readers_today')->default(0);

    // Thống kê phạt
    $table->decimal('total_fines', 10, 2)->default(0);
    $table->decimal('paid_fines', 10, 2)->default(0);
    $table->decimal('unpaid_fines', 10, 2)->default(0);

    $table->timestamps();
});
```

---

### 7. **Thêm bảng Settings/Configurations**

```php
Schema::create('library_settings', function (Blueprint $table) {
    $table->increments('id');
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->string('type')->default('string'); // string, integer, boolean, json
    $table->string('group')->default('general');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

**Default settings:**
```php
[
    'loan_duration_days' => 30,
    'max_renewals' => 2,
    'max_books_per_reader' => 5,
    'overdue_fine_per_day' => 5000,
    'lost_book_fine_multiplier' => 2,
    'damaged_book_fine_percentage' => 50,
    'reservation_expiry_hours' => 48,
    'library_opening_time' => '08:00',
    'library_closing_time' => '17:00',
]
```

---

### 8. **Cải tiến Categories Table**

Thêm classification system chuẩn thư viện:

```php
// Thêm vào categories table
$table->string('dewey_decimal')->nullable()->comment('Mã phân loại Dewey');
$table->string('lcc')->nullable()->comment('Library of Congress Classification');
$table->string('ddc')->nullable()->comment('Dewey Decimal Classification');
$table->string('icon')->nullable()->comment('Icon cho category');
$table->string('color')->nullable()->comment('Màu sắc');
```

---

### 9. **Thêm bảng Notifications**

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

    $table->enum('type', [
        'loan_due_soon',
        'loan_overdue',
        'reservation_ready',
        'reservation_expired',
        'fine_unpaid',
        'card_expiring',
        'new_book',
        'system'
    ]);

    $table->string('title');
    $table->text('message');
    $table->json('data')->nullable();

    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();

    $table->timestamps();

    $table->index(['user_id', 'is_read']);
});
```

---

### 10. **Thêm bảng Activity Logs**

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedInteger('user_id')->nullable();
    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

    $table->string('log_name')->nullable();
    $table->text('description');
    $table->string('subject_type')->nullable();
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->string('event')->nullable();
    $table->string('causer_type')->nullable();
    $table->unsignedBigInteger('causer_id')->nullable();
    $table->json('properties')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();

    $table->timestamps();

    $table->index(['subject_type', 'subject_id']);
    $table->index(['causer_type', 'causer_id']);
});
```

---

## 📊 Indexes và Performance Optimization

### Recommended Indexes

```php
// Books table
$table->index('title');
$table->index('isbn');
$table->index(['category_id', 'status']);
$table->index('published_year');
$table->fullText(['title', 'keywords', 'summary']);

// Book Copies table
$table->index('barcode');
$table->index(['book_id', 'status']);
$table->index('location');

// Loans table
$table->index(['reader_id', 'status']);
$table->index(['book_copy_id', 'status']);
$table->index(['due_date', 'status']);
$table->index('loan_date');

// Readers table
$table->index('reader_code');
$table->index('student_code');
$table->index('card_number');
$table->index(['card_status', 'is_active']);

// Fines table
$table->index(['user_id', 'status']);
$table->index(['loan_id', 'status']);
$table->index('paid_date');
```

---

## 🔄 Migration Strategy

### Bước 1: Tạo các bảng mới (không ảnh hưởng hiện tại)
```bash
php artisan make:migration create_readers_table
php artisan make:migration create_loan_histories_table
php artisan make:migration create_book_reviews_table
php artisan make:migration create_library_statistics_table
php artisan make:migration create_library_settings_table
php artisan make:migration create_notifications_table
php artisan make:migration create_activity_logs_table
```

### Bước 2: Cập nhật các bảng hiện có
```bash
php artisan make:migration add_fields_to_books_table
php artisan make:migration add_fields_to_loans_table
php artisan make:migration add_fields_to_categories_table
php artisan make:migration add_indexes_to_tables
```

### Bước 3: Migrate dữ liệu
```bash
php artisan make:command MigrateUsersToReaders
php artisan make:command CalculateBookStatistics
php artisan make:command GenerateLibrarySettings
```

### Bước 4: Testing
```bash
php artisan test --filter=DatabaseStructureTest
```

---

## 🎯 Priority Implementation

### Phase 1 - CRITICAL (Tuần 1-2)
1. ✅ Tạo bảng `readers` và migrate data từ `users`
2. ✅ Cập nhật `loans` table với `reader_id`
3. ✅ Thêm `loan_histories` table
4. ✅ Thêm indexes cơ bản

### Phase 2 - HIGH (Tuần 3-4)
1. ✅ Cập nhật `books` table với các trường mới
2. ✅ Tạo `library_settings` table
3. ✅ Tạo `library_statistics` table
4. ✅ Thêm fulltext search indexes

### Phase 3 - MEDIUM (Tuần 5-6)
1. ✅ Tạo `notifications` table
2. ✅ Tạo `book_reviews` table
3. ✅ Cập nhật `categories` với classification codes
4. ✅ Tạo `activity_logs` table

### Phase 4 - NICE TO HAVE (Sau khi hoàn thành Phase 1-3)
1. ⭐ Advanced search features
2. ⭐ Recommendation system
3. ⭐ Analytics dashboard
4. ⭐ Mobile app API optimization

---

## 📝 Model Relationships

### Reader Model
```php
class Reader extends Model
{
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function faculty() {
        return $this->belongsTo(Faculty::class);
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function loans() {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans() {
        return $this->hasMany(Loan::class)->where('status', 'active');
    }

    public function fines() {
        return $this->hasMany(Fine::class, 'user_id', 'user_id');
    }

    public function unpaidFines() {
        return $this->fines()->where('status', 'unpaid');
    }
}
```

### Book Model (Updated)
```php
class Book extends Model
{
    public function copies() {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies() {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }

    public function authors() {
        return $this->belongsToMany(Author::class, 'book_author')
                    ->withPivot('role', 'order')
                    ->orderBy('order');
    }

    public function reviews() {
        return $this->hasMany(BookReview::class);
    }

    public function updateStatistics() {
        $this->total_copies = $this->copies()->count();
        $this->available_copies = $this->availableCopies()->count();
        $this->average_rating = $this->reviews()->avg('rating');
        $this->save();
    }
}
```

---

## 🚀 API Endpoints cần cập nhật

### Readers
```
GET    /api/readers
POST   /api/readers
GET    /api/readers/{id}
PUT    /api/readers/{id}
DELETE /api/readers/{id}
GET    /api/readers/{id}/loans
GET    /api/readers/{id}/fines
POST   /api/readers/{id}/renew-card
```

### Enhanced Loans
```
GET    /api/loans
POST   /api/loans
GET    /api/loans/{id}
PUT    /api/loans/{id}
POST   /api/loans/{id}/renew
POST   /api/loans/{id}/return
GET    /api/loans/{id}/history
GET    /api/loans/overdue
GET    /api/loans/due-soon
```

### Statistics
```
GET    /api/statistics/dashboard
GET    /api/statistics/books
GET    /api/statistics/loans
GET    /api/statistics/readers
GET    /api/statistics/fines
GET    /api/statistics/export
```

---

## 🔒 Security Considerations

### 1. Data Privacy
- Readers' personal information (phone, address) chỉ accessible by admin
- Email masking cho non-admin users
- GDPR compliance cho data export/delete

### 2. Access Control
```php
// Permissions cần thêm
'readers.view'
'readers.create'
'readers.update'
'readers.delete'
'readers.view-sensitive' // phone, address
'statistics.view'
'statistics.export'
'settings.view'
'settings.update'
```

### 3. Audit Trail
- Mọi thay đổi quan trọng phải log vào `activity_logs`
- Track IP address và user agent
- Retention policy: 1 năm

---

## 📈 Performance Benchmarks

### Target Metrics
- Book search: < 100ms
- Loan creation: < 200ms
- Dashboard load: < 500ms
- Statistics generation: < 1s
- Export reports: < 5s

### Optimization Strategies
1. **Caching**: Redis cho statistics và settings
2. **Eager Loading**: Luôn dùng `with()` cho relationships
3. **Query Optimization**: Sử dụng indexes và avoid N+1
4. **Background Jobs**: Statistics calculation, notifications
5. **Database Partitioning**: Nếu data > 1M records

---

## 🧪 Testing Checklist

### Unit Tests
- [ ] Reader CRUD operations
- [ ] Loan business logic (create, renew, return)
- [ ] Fine calculations
- [ ] Statistics calculations
- [ ] Settings management

### Integration Tests
- [ ] Complete loan workflow
- [ ] Overdue detection and fine generation
- [ ] Notification sending
- [ ] Report generation

### Performance Tests
- [ ] 1000 concurrent book searches
- [ ] 100 concurrent loan creations
- [ ] Dashboard load with 10K+ records

---

## 📚 References

1. **Library Standards**
   - Dewey Decimal Classification
   - Library of Congress Classification
   - MARC 21 Format

2. **Similar Systems**
   - Koha ILS
   - Evergreen ILS
   - OpenBiblio

3. **Best Practices**
   - Laravel Database Best Practices
   - MySQL Performance Tuning
   - Library Management System Design Patterns

---

## 🎓 Conclusion

Đề xuất này tập trung vào:

1. **Tách biệt concerns**: Users ≠ Readers
2. **Tracking đầy đủ**: History, logs, statistics
3. **Flexibility**: Settings, notifications, reviews
4. **Performance**: Indexes, caching, optimization
5. **Scalability**: Có thể mở rộng cho mobile app, API

**Next Steps:**
1. Review và approve đề xuất
2. Tạo migrations theo priority phases
3. Update models và relationships
4. Update API endpoints
5. Update frontend để sử dụng structure mới
6. Testing và deployment

---

**Document Version:** 1.0
**Last Updated:** 2026-02-02
**Author:** Antigravity AI
**Status:** Pending Review
