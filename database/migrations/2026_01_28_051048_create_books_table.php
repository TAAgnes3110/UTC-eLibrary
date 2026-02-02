<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('Tên sách');
            $table->string('classification_code')->nullable()->comment('Phân loại sách');
            $table->string('classification_detail')->nullable()->comment('Phân loại chi tiết');
            $table->unsignedInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->unsignedInteger('publisher_id')->nullable();
            $table->foreign('publisher_id')->references('id')->on('publishers')->onDelete('set null');
            $table->string('publication_place')->nullable()->comment('Nơi xuất bản');
            $table->year('published_year')->nullable()->comment('Năm xuất bản');
            $table->integer('total_pages')->nullable()->comment('Số trang');
            $table->string('book_size')->nullable()->comment('Khổ sách');
            $table->integer('volume_number')->nullable()->comment('Tập số');
            $table->decimal('price', 10, 2)->nullable()->comment('Giá sách');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->enum('status', ['available', 'unavailable', 'processing'])->default('available')->comment('Trạng thái');
            $table->json('params')->nullable()->comment('Tham số bổ sung');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('book_author', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('book_id');
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->unsignedInteger('author_id');
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
            $table->enum('role', ['author', 'co-author', 'editor', 'translator'])->default('author')->comment('Vai trò');
            $table->integer('order')->default(0)->comment('Thứ tự');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_author');
        Schema::dropIfExists('books');
    }
};
