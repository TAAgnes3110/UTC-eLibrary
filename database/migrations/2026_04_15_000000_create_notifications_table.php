<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('recipient_type', 20)->index()->comment('Nhóm nhận: admin|user');
            $table->unsignedInteger('recipient_id')->index()->comment('ID người nhận thông báo');
            $table->string('type', 120)->index()->comment('Loại thông báo nghiệp vụ');
            $table->string('title', 255)->comment('Tiêu đề hiển thị');
            $table->text('message')->comment('Nội dung thông báo');
            $table->string('severity', 20)->default('info')->comment('Mức độ: info|warning|critical');
            $table->string('entity_type', 100)->nullable()->comment('Loại thực thể liên quan');
            $table->unsignedInteger('entity_id')->nullable()->comment('ID thực thể liên quan');
            $table->string('action_url', 500)->nullable()->comment('URL điều hướng khi click thông báo');
            $table->json('meta')->nullable()->comment('Dữ liệu mở rộng cho frontend');
            $table->string('dedupe_key', 191)->nullable()->comment('Khóa chống trùng thông báo');
            $table->timestamp('read_at')->nullable()->index()->comment('Thời điểm đã đọc');

            $table->userAuditColumns();
            $table->timestamps();

            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('dedupe_key');
            $table->index(['recipient_type', 'recipient_id', 'id'], 'notifications_recipient_stream_idx');
            $table->index(['recipient_type', 'recipient_id', 'read_at'], 'notifications_recipient_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

