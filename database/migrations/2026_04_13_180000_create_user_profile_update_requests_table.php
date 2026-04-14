<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profile_update_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index()->comment('Người tạo yêu cầu');
            $table->string('requested_code', 255)->nullable()->comment('Mã định danh mới');
            $table->unsignedInteger('requested_faculty_id')->nullable()->comment('Khoa mới');
            $table->unsignedInteger('requested_period_id')->nullable()->comment('Niên khóa mới');
            $table->string('requested_class_code', 100)->nullable()->comment('Lớp mới');
            $table->string('proof_image_path', 500)->comment('Ảnh minh chứng');
            $table->string('status', 20)->default('pending')->index()->comment('pending|approved|rejected');
            $table->text('reason')->nullable()->comment('Lý do người dùng gửi');
            $table->text('review_note')->nullable()->comment('Ghi chú duyệt/từ chối');
            $table->unsignedInteger('reviewed_by')->nullable()->comment('Người duyệt');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable()->comment('Thời điểm áp dụng vào users');

            $table->userAuditColumns();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_faculty_id')->references('id')->on('faculties')->onDelete('set null');
            $table->foreign('requested_period_id')->references('id')->on('periods')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profile_update_requests');
    }
};

