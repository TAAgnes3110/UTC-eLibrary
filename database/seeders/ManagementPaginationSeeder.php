<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ManagementPaginationSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::query()->where('email', 'librarian@utc.edu.vn')->first()
            ?? User::query()->where('email', 'admin@utc.edu.vn')->first();

        $this->seedLoans($staff);
    }

    private function seedLoans(?User $staff): void
    {
        $cards = LibraryCard::query()
            ->whereIn('workflow_status', [LibraryCard::WORKFLOW_ACTIVE, LibraryCard::WORKFLOW_PENDING_PICKUP])
            ->orderBy('id')
            ->get();

        $books = Book::query()
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->get();

        if ($cards->isEmpty() || $books->isEmpty()) {
            return;
        }

        $creatorId = $staff?->id;

        // Dữ liệu mẫu gọn: 15 phiếu mượn.
        for ($i = 1; $i <= 15; $i++) {
            $card = $cards[($i - 1) % $cards->count()];
            $loanDate = Carbon::today()->subDays(($i % 20) + 1);
            $dueDate = $loanDate->copy()->addDays(10 + ($i % 8));
            $status = match (true) {
                $i % 6 === 0 => Loan::STATUS_OVERDUE,
                $i % 3 === 0 => Loan::STATUS_RETURNED,
                default => Loan::STATUS_BORROWED,
            };
            $returnDate = $status === Loan::STATUS_RETURNED
                ? $dueDate->copy()->subDays($i % 3)->toDateString()
                : null;

            $loan = Loan::query()->updateOrCreate(
                ['loan_code' => sprintf('LDM%04d', $i)],
                [
                    'library_card_id' => $card->id,
                    'loan_type' => $i % 5 === 0 ? Loan::TYPE_ONSITE : Loan::TYPE_HOME,
                    'loan_date' => $loanDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'return_date' => $returnDate,
                    'status' => $status,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]
            );

            LoanItem::query()->where('loan_id', $loan->id)->delete();

            $itemsCount = ($i % 2) + 1;
            for ($j = 0; $j < $itemsCount; $j++) {
                $book = $books[($i + $j) % $books->count()];
                $conditionOnLoan = 'tot';
                $conditionOnReturn = $status === Loan::STATUS_RETURNED
                    ? (($i + $j) % 12 === 0 ? 'hong' : 'tot')
                    : null;

                LoanItem::query()->create([
                    'loan_id' => $loan->id,
                    'book_id' => $book->id,
                    'quantity' => 1,
                    'condition_on_loan' => $conditionOnLoan,
                    'condition_on_return' => $conditionOnReturn,
                    'fine_amount' => $status === Loan::STATUS_RETURNED && $conditionOnReturn === 'hong' ? 15000 : 0,
                    'notes' => 'Dữ liệu mẫu nghiệp vụ mượn/trả',
                ]);
            }
        }
    }
}
