<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('resource_kind', 20)->default('print')->after('warehouse_id');
            $table->string('access_mode', 20)->default('circulation_only')->after('resource_kind');
            $table->index(['resource_kind', 'classification_id'], 'books_resource_kind_classification_idx');
        });

        Schema::create('digital_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->boolean('is_primary')->default(false);
            $table->string('storage_disk', 50)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->char('checksum_sha256', 64)->nullable()->index();
            $table->string('visibility', 20)->default('internal')->index();
            $table->date('embargo_until')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['book_id', 'is_primary']);
            $table->index(['book_id', 'version']);
        });

        Schema::create('thesis_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->unique()->constrained('books')->cascadeOnDelete();
            $table->string('work_type', 40)->index();
            $table->string('degree_program', 150)->nullable();
            $table->string('supervisor_name')->nullable();
            $table->unsignedInteger('supervisor_user_id')->nullable()->index();
            $table->unsignedSmallInteger('defense_year')->nullable();
            $table->text('keywords')->nullable();
            $table->text('abstract_text')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();

            $table->foreign('supervisor_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('digital_access_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreignId('digital_asset_id')->constrained('digital_assets')->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('max_downloads')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'digital_asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_access_sessions');
        Schema::dropIfExists('thesis_metadata');
        Schema::dropIfExists('digital_assets');

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('books_resource_kind_classification_idx');
            $table->dropColumn(['resource_kind', 'access_mode']);
        });
    }
};
