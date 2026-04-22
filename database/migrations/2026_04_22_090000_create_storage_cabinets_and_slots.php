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
            $table->unsignedInteger('capacity_total')->default(0)->comment('Tổng sức chứa các ngăn');
            $table->unsignedInteger('current_quantity')->default(0)->comment('Tổng số lượng hiện có trong tủ');
            $table->boolean('is_active')->default(true)->index();
            $table->json('params')->nullable();
            $table->userAuditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'classification_id'], 'storage_cabinets_wh_class_idx');
            $table->index(['warehouse_id', 'code'], 'storage_cabinets_wh_code_idx');
        });

        Schema::create('storage_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_cabinet_id')->constrained('storage_cabinets')->cascadeOnDelete();
            $table->foreignId('classification_detail_id')->nullable()->constrained('classification_details')->nullOnDelete();
            $table->string('slot_code', 80)->nullable()->comment('Mã ngăn lưu trữ');
            $table->string('slot_name', 180)->comment('Tên ngăn lưu trữ');
            $table->unsignedInteger('capacity')->default(30)->comment('Sức chứa ngăn');
            $table->unsignedInteger('current_quantity')->default(0)->comment('Số lượng hiện có trong ngăn');
            $table->boolean('is_active')->default(true)->index();
            $table->json('params')->nullable();
            $table->userAuditColumns();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['storage_cabinet_id', 'classification_detail_id'], 'storage_slots_cabinet_detail_idx');
            $table->index(['storage_cabinet_id', 'slot_code'], 'storage_slots_cabinet_code_idx');
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->foreignId('storage_slot_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('storage_slots')
                ->nullOnDelete();
            $table->index(['warehouse_id', 'storage_slot_id'], 'book_copies_warehouse_storage_slot_idx');
        });
    }

    public function down(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropIndex('book_copies_warehouse_storage_slot_idx');
            $table->dropConstrainedForeignId('storage_slot_id');
        });

        Schema::dropIfExists('storage_slots');
        Schema::dropIfExists('storage_cabinets');
    }
};
