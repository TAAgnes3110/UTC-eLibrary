<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            // Số đăng ký cá biệt (có thể để trống, hệ thống sẽ tự sinh nếu cần)
            $table->string('registration_number')->nullable()->unique()->index();

            // Mã sách (quy tắc danh mục riêng)
            $table->string('book_code')->nullable()->index();

            // Tên sách + bổ sung tên sách
            $table->string('title')->index();
            $table->string('sub_title')->nullable();

            // Ngôn ngữ, lần XB, năm XB
            $table->string('language', 50)->nullable();
            $table->string('edition', 50)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();

            // Thông tin vật lý
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('illustration_pages')->nullable();
            $table->string('book_size', 50)->nullable();
            $table->unsignedBigInteger('price')->nullable();

            // Số lượng đầu sách (theo file nhập)
            $table->unsignedInteger('quantity')->default(0);

            // Nội dung / mô tả
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->string('series_name')->nullable();

            // Nơi XB và vị trí lưu trữ
            $table->string('publisher_place')->nullable();
            $table->string('cabinet', 100)->nullable();
            $table->string('shelf', 100)->nullable();

            // Ảnh bìa
            $table->string('cover_image')->nullable();

            // Quan hệ danh mục
            $table->foreignId('classification_id')->nullable()->constrained('classifications')->nullOnDelete();
            $table->foreignId('classification_detail_id')->nullable()->constrained('classification_details')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // Các thông tin phụ: tài liệu kèm theo, tủ/ngăn chi tiết, flags...
            $table->json('params')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['classification_id', 'classification_detail_id']);
            $table->index(['warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};

