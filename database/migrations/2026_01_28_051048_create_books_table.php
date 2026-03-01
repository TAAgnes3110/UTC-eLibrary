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
            $table->string('type', 30)->default('book')->index();
            $table->string('title')->index();
            $table->string('isbn', 20)->nullable();
            $table->string('classification_code')->nullable()->index();
            $table->string('classification_detail')->nullable();
            $table->string('edition', 50)->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('faculty_id')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->string('cohort', 20)->nullable();
            $table->unsignedInteger('publisher_id')->nullable();
            $table->string('publication_place')->nullable();
            $table->year('published_year')->nullable()->index();
            $table->unsignedInteger('total_pages')->nullable();
            $table->string('book_size', 50)->nullable();
            $table->unsignedSmallInteger('volume_number')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->unsignedInteger('total_copies')->default(0);
            $table->unsignedInteger('available_copies')->default(0);
            $table->json('params')->nullable();
            $table->boolean('is_digital')->default(false);
            $table->string('file_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('publisher_id')->references('id')->on('publishers')->onDelete('set null');
        });

        Schema::create('book_author', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('book_id');
            $table->unsignedInteger('author_id');
            $table->string('role', 20)->default('author');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_author');
        Schema::dropIfExists('books');
    }
};
