<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $types = ['book', 'textbook', 'thesis', 'dissertation', 'research', 'magazine', 'other'];
        $statuses = ['available', 'unavailable', 'processing'];
        $roles = ['author', 'co-author', 'editor', 'translator', 'supervisor'];

        Schema::create('books', function (Blueprint $table) use ($types, $statuses) {
            $table->increments('id');
            $table->enum('type', $types)->default('book')->index();
            $table->string('title');
            $table->string('classification_code')->nullable();
            $table->string('classification_detail', 255)->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('faculty_id')->nullable();
            $table->unsignedInteger('publisher_id')->nullable();
            $table->string('publication_place', 255)->nullable();
            $table->year('published_year')->nullable();
            $table->unsignedInteger('total_pages')->nullable();
            $table->string('book_size', 50)->nullable();
            $table->unsignedSmallInteger('volume_number')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', $statuses)->default('available');
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('set null');
            $table->foreign('publisher_id')->references('id')->on('publishers')->onDelete('set null');
        });

        Schema::create('book_author', function (Blueprint $table) use ($roles) {
            $table->increments('id');
            $table->unsignedInteger('book_id');
            $table->unsignedInteger('author_id');
            $table->enum('role', $roles)->default('author');
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
