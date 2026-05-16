<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_primary')->default(false);
            $table->string('storage_disk', 50)->default('local')
                ->comment('Disk lưu PDF gốc — config filesystems.digital_assets_disk');
            $table->string('path');
            $table->string('preview_path')->nullable()
                ->comment('PDF N trang đầu (FPDI), cùng disk với bản gốc');
            $table->unsignedTinyInteger('preview_page_count')->nullable();
            $table->timestamp('preview_generated_at')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->unsignedBigInteger('view_count')->default(0)->comment('Lượt xem trang chi tiết + xem trước PDF');
            $table->unsignedBigInteger('download_count')->default(0)->comment('Lượt tải PDF gốc');
            $table->json('preview_display')->nullable()
                ->comment('PNG từng trang xem trước (preview_display.pages[].path)');
            $table->char('checksum_sha256', 64)->nullable()->index();
            $table->string('visibility', 20)->default('internal')->index();
            $table->date('embargo_until')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->userAuditColumns();

            $table->index(['book_id', 'is_primary']);
            $table->index(['book_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_assets');
    }
};
