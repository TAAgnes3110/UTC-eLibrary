<?php

namespace Database\Seeders;

use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/** Vài phiếu mượn mẫu: 1 đang mượn (active), 1 đã trả (returned) — để test API loans. */
class LoanSeeder extends Seeder
{
    public function run(): void
    {
        $librarian = User::where('email', 'librarian@example.com')->first();
        $reader = User::where('email', 'user@example.com')->first();
        if (!$librarian || !$reader) {
            return;
        }

        // Copy đang available để tạo phiếu active
        $copyActive = BookCopy::where('status', 'available')->first();
        if ($copyActive) {
            $copyActive->update(['status' => 'borrowed']);
            Loan::firstOrCreate(
                [
                    'user_id' => $reader->id,
                    'book_copy_id' => $copyActive->id,
                    'loan_date' => Carbon::today()->subDays(5),
                ],
                [
                    'librarian_id' => $librarian->id,
                    'due_date' => Carbon::today()->addDays(25),
                    'status' => 'active',
                    'condition_on_loan' => 'good',
                    'renewal_count' => 0,
                    'max_renewals' => 2,
                ]
            );
        }

        // Phiếu đã trả (returned): copy vẫn available vì đã trả
        $copyReturned = BookCopy::where('status', 'available')
            ->when($copyActive, fn ($q) => $q->where('id', '!=', $copyActive->id))
            ->first();
        if ($copyReturned) {
            Loan::firstOrCreate(
                [
                    'user_id' => $reader->id,
                    'book_copy_id' => $copyReturned->id,
                    'loan_date' => Carbon::today()->subDays(40),
                ],
                [
                    'librarian_id' => $librarian->id,
                    'due_date' => Carbon::today()->subDays(10),
                    'return_date' => Carbon::today()->subDays(9),
                    'status' => 'returned',
                    'condition_on_loan' => 'good',
                    'condition_on_return' => 'good',
                    'renewal_count' => 1,
                    'max_renewals' => 2,
                ]
            );
        }
    }
}
