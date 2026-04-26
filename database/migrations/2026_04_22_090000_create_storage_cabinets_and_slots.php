<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_cabinets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('classification_id')->nullable()->constrained('classifications')->nullOnDelete();
            $table->string('code', 60)->nullable()->comment('Mã tủ lưu trữ');
            $table->string('name', 160)->comment('Tên tủ lưu trữ');
            $table->unsignedInteger('capacity_total')->default(0)->comment('Tổng sức chứa tủ');
            $table->unsignedInteger('current_quantity')->default(0)->comment('Tổng số lượng hiện có trong tủ');
            $table->boolean('is_active')->default(true)->index();
            $table->json('params')->nullable();
            $table->userAuditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'classification_id'], 'storage_cabinets_wh_class_idx');
            $table->index(['warehouse_id', 'code'], 'storage_cabinets_wh_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_cabinets');
    }
};
