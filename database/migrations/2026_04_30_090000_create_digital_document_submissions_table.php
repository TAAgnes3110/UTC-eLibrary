<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_document_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('submitted_by');
            $table->foreign('submitted_by')->references('id')->on('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('author_names', 500)->nullable()->comment('Danh sách tác giả, phân tách bằng ;');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->string('cover_image_path', 500)->nullable()->comment('Ảnh bìa tùy chọn do độc giả gửi kèm');
            $table->string('status', 20)->default('pending')->comment('pending|approved|rejected');
            $table->text('review_note')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_book_id')->nullable()->constrained('books')->nullOnDelete();
            $table->timestamp('user_hidden_at')->nullable()->comment('Độc giả ẩn khỏi danh sách của mình; thủ thư vẫn quản lý đầy đủ');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_hidden_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_document_submissions');
    }
};
