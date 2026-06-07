<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->unsignedBigInteger('book_price_at_loan')
                ->nullable()
                ->after('quantity')
                ->comment('Giá bìa chốt tại thời điểm mượn — dùng tính phạt khi trả, không đổi theo giá sách sau này');
        });

        // Phiếu cũ: không có lịch sử giá — gán giá hiện tại làm fallback tốt nhất có thể.
        $legacyItems = DB::table('loan_items')
            ->join('books', 'loan_items.book_id', '=', 'books.id')
            ->whereNull('loan_items.book_price_at_loan')
            ->whereNotNull('books.price')
            ->select('loan_items.id as loan_item_id', 'books.price as book_price')
            ->get();

        foreach ($legacyItems as $row) {
            DB::table('loan_items')
                ->where('id', $row->loan_item_id)
                ->update(['book_price_at_loan' => $row->book_price]);
        }
    }

    public function down(): void
    {
        Schema::table('loan_items', function (Blueprint $table) {
            $table->dropColumn('book_price_at_loan');
        });
    }
};
