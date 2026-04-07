<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('user_type', 50)->nullable()->index()->comment('Khớp RoleType/user; null = mặc định mọi đối tượng');
            $table->unsignedInteger('max_books')->default(0)->comment('Số đầu sách mượn tối đa đồng thời');
            $table->unsignedInteger('max_days')->default(0)->comment('Thời hạn mượn (ngày)');
            $table->unsignedTinyInteger('max_renewals')->default(0)->comment('Số lần gia hạn tối đa');
            $table->decimal('overdue_fine_per_day', 10, 2)->default(0)->comment('Phạt mỗi ngày trễ hạn');
            $table->boolean('allow_home')->default(true)->comment('Được mượn về nhà');
            $table->boolean('allow_onsite')->default(true)->comment('Được đọc/mượn tại chỗ');
            $table->json('params')->nullable()->comment('Mở rộng cấu hình');

            $table->userAuditColumns();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_policies');
    }
};
