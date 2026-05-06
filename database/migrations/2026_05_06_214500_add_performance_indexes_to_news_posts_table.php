<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table): void {
            $table->index(['status', 'type', 'published_at', 'id'], 'news_posts_status_type_published_id_idx');
            $table->index(['type', 'status', 'published_at', 'id'], 'news_posts_type_status_published_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table): void {
            $table->dropIndex('news_posts_status_type_published_id_idx');
            $table->dropIndex('news_posts_type_status_published_id_idx');
        });
    }
};
