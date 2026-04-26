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
            $table->string('registration_number')->nullable()->unique();
            $table->string('book_code')->nullable()->index();
            $table->string('title')->required()->index();
            $table->string('sub_title')->nullable();
            $table->string('language', 50)->nullable();
            $table->string('edition', 50)->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('illustration_pages')->nullable();
            $table->string('book_size', 50)->nullable();
            $table->unsignedBigInteger('price')->nullable();
            $table->unsignedInteger('quantity')->default(0)->required();
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->string('series_name')->nullable();
            $table->string('publisher_place')->nullable();
            $table->string('cabinet', 100)->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('classification_id')->nullable()->constrained('classifications')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->string('resource_type', 20)->default('reference')->comment('Loại tài liệu: textbook, reference, digital');
            $table->string('access_mode', 20)->default('circulation_only')->comment('circulation_only|onsite|digital|…');

            $table->json('params')->nullable();

            $table->userAuditColumns();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['classification_id']);
            $table->index(['warehouse_id']);
            $table->index(['resource_type', 'classification_id'], 'books_resource_type_classification_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
