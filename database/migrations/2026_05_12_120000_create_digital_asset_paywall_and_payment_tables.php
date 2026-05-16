<?php

use App\Models\DigitalAsset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paywall tài liệu số: tải PDF sau thanh toán SePay.
 *
 * @see DigitalAsset
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_asset_paywall_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_asset_id')
                ->unique()
                ->constrained('digital_assets')
                ->cascadeOnDelete();
            $table->boolean('is_paywall_enabled')->default(true)
                ->comment('false: không thu phí, tải PDF miễn phí');
            $table->unsignedBigInteger('pdf_download_price_vnd')
                ->comment('Giá tải PDF toàn bộ (VND, số nguyên)');
            $table->char('currency', 3)->default('VND');
            $table->text('internal_note')->nullable()->comment('Ghi chú nội bộ thủ thư, không hiển thị độc giả');
            $table->timestamps();

            $table->userAuditColumns();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('type', 30)->comment('loan|digital_purchase');
            $table->timestamp('price_locked_until')->nullable()->index()
                ->comment('Chỉ áp dụng cho giỏ digital_purchase nếu muốn giữ giá tạm thời; null = chưa khóa');
            $table->timestamps();

            $table->unique(['user_id', 'type']);
            $table->index(['type', 'updated_at']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->string('item_type', 40)->comment('loan_book_copy|digital_asset_unlock');
            $table->foreignId('digital_asset_id')->nullable()->constrained('digital_assets')->nullOnDelete();
            $table->unsignedBigInteger('book_copy_id')->nullable()->comment('FK tới book_copies nếu item_type=loan_book_copy');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_vnd_snapshot')->nullable()
                ->comment('Snapshot giá tại thời điểm add vào giỏ (digital); loan để null');
            $table->unsignedBigInteger('line_total_vnd_snapshot')->nullable()
                ->comment('Snapshot thành tiền; có thể tính = unit_price * quantity');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'item_type']);
            $table->index(['digital_asset_id']);
            $table->index(['book_copy_id']);
            $table->unique(['cart_id', 'digital_asset_id'], 'cart_items_cart_digital_asset_unique');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique()->comment('Mã đơn an toàn khi expose ra client / gateway');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('type', 30)->comment('digital_purchase');
            $table->string('status', 24)->default('pending')->comment('pending|paid|expired|cancelled|failed');
            $table->unsignedBigInteger('subtotal_vnd_snapshot');
            $table->unsignedBigInteger('total_vnd_snapshot');
            $table->char('currency', 3)->default('VND');
            $table->timestamp('price_locked_until')->nullable()->index()
                ->comment('Hết hạn giữ giá / thanh toán nếu vẫn pending');
            $table->timestamp('paid_at')->nullable()->index();
            $table->string('gateway', 32)->default('sepay')->comment('Cổng thanh toán, mặc định SePay');
            $table->string('merchant_reference', 80)->unique()->comment('Mã tham chiếu gửi gateway (unique, idempotent)');
            $table->json('gateway_init_payload')->nullable()->comment('Dữ liệu khởi tạo thanh toán (QR/VA/URL…), tránh PII');
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['type', 'status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('item_type', 40)->comment('digital_asset_unlock');
            $table->foreignId('digital_asset_id')->constrained('digital_assets')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_vnd_snapshot');
            $table->unsignedBigInteger('line_total_vnd_snapshot');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'item_type']);
            $table->index(['digital_asset_id']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway', 32)->default('sepay');
            $table->string('status', 24)->default('pending')->comment('pending|success|failed');
            $table->unsignedBigInteger('amount_vnd');
            $table->char('currency', 3)->default('VND');
            $table->string('gateway_transaction_id', 120)->nullable()->index();
            $table->string('idempotency_key', 120)->nullable()->unique()
                ->comment('Chống webhook bắn lại / xử lý lặp');
            $table->timestamp('verified_at')->nullable()->index();
            $table->json('callback_meta')->nullable()->comment('Meta sau xác minh webhook, tránh lưu payload đầy đủ nếu có PII');
            $table->timestamps();

            $table->index(['gateway', 'status', 'created_at']);
        });

        Schema::create('digital_asset_pdf_download_entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('digital_asset_id')->constrained('digital_assets')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()
                ->constrained('orders')
                ->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index()
                ->comment('null: quyền không hết hạn theo thời gian (trừ khi thu hồi)');
            $table->timestamp('revoked_at')->nullable()->index()
                ->comment('Thu hồi thủ công / vi phạm');
            $table->timestamps();

            $table->unique(['user_id', 'digital_asset_id'], 'digital_asset_entitlement_user_asset_unique');
            $table->index(['digital_asset_id', 'revoked_at'], 'da_entitlement_asset_revoked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_asset_pdf_download_entitlements');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('digital_asset_paywall_settings');
    }
};
