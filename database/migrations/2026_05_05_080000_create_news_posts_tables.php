<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 255)->unique();
            $table->string('title', 255);
            $table->longText('content');
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('status', 20)->default('draft')->comment('draft|published')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->userAuditColumns();
            $table->timestamps();
        });

        Schema::create('news_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->constrained('news_posts')->cascadeOnDelete();
            $table->string('storage_disk', 20)->default('public');
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->timestamps();

            $table->index(['news_post_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_attachments');
        Schema::dropIfExists('news_posts');
    }
};
