<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nội dung website: trang tĩnh, tin/bài, mô tả dịch vụ — một bảng, phân loại bằng {@see $table->string('kind')}.
     */
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index()->comment('page|post|service');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('subtype', 50)->nullable()->index()->comment('page: intro|rule…; post: news|…; service: mã nghiệp vụ (vd MUON_VE_NHA)');
            $table->unsignedInteger('author_id')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('published_at')->nullable();
            $table->json('params')->nullable();

            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            $table->userAuditColumns();

            $table->timestamps();

            $table->index(['kind', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
