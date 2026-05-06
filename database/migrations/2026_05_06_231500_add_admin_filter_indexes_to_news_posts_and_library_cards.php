<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_posts', function (Blueprint $table): void {
            // Admin list: status/type + sort by id.
            $table->index(['status', 'id'], 'news_posts_status_id_idx');
            $table->index(['status', 'type', 'id'], 'news_posts_status_type_id_idx');
        });

        Schema::table('library_cards', function (Blueprint $table): void {
            // Admin list mặc định: workflow/status/holder + sort created_at, id.
            $table->index(['workflow_status', 'created_at', 'id'], 'library_cards_workflow_created_id_idx');
            $table->index(['holder_type', 'created_at', 'id'], 'library_cards_holder_created_id_idx');
            $table->index(['status', 'created_at', 'id'], 'library_cards_status_created_id_idx');
            $table->index(['workflow_status', 'holder_type', 'status', 'created_at', 'id'], 'library_cards_wf_holder_status_created_id_idx');

            // Sort theo họ tên trong admin.
            $table->index(['full_name', 'id'], 'library_cards_full_name_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('news_posts', function (Blueprint $table): void {
            $table->dropIndex('news_posts_status_id_idx');
            $table->dropIndex('news_posts_status_type_id_idx');
        });

        Schema::table('library_cards', function (Blueprint $table): void {
            $table->dropIndex('library_cards_workflow_created_id_idx');
            $table->dropIndex('library_cards_holder_created_id_idx');
            $table->dropIndex('library_cards_status_created_id_idx');
            $table->dropIndex('library_cards_wf_holder_status_created_id_idx');
            $table->dropIndex('library_cards_full_name_id_idx');
        });
    }
};
