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
