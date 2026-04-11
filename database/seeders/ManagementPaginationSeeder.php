<?php

namespace Database\Seeders;

use App\Enums\AccessMode;
use App\Enums\LibraryCardStatus;
use App\Enums\ResourceType;
use App\Enums\RoleType;
use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Period;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ManagementPaginationSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::query()->where('email', 'librarian@utc.edu.vn')->first()
            ?? User::query()->where('email', 'admin@utc.edu.vn')->first();

        $this->seedUsers($staff);
        $this->seedBooks($staff);
        $this->seedLibraryCards($staff);
        $this->seedLoans($staff);
    }

    private function seedUsers(?User $staff): void
    {
        $faculty = Faculty::query()->first();
        $period = Period::query()->first();
        $creatorId = $staff?->id;

        for ($i = 1; $i <= 60; $i++) {
            $role = match ($i % 3) {
                0 => RoleType::STUDENT,
                1 => RoleType::TEACHER,
                default => RoleType::MEMBER,
            };

            $email = sprintf('demo.user.%03d@utc.local', $i);
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'code' => sprintf('009%09d', $i),
                    'name' => sprintf('Người dùng demo %03d', $i),
                    'phone' => sprintf('0909%06d', $i),
                    'password' => 'password',
                    'user_type' => $role->value,
                    'faculty_id' => $faculty?->id,
                    'period_id' => $role === RoleType::STUDENT ? $period?->id : null,
                    'class_code' => $role === RoleType::STUDENT ? sprintf('K67-%02d', ($i % 20) + 1) : null,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]
            );
        }
    }

    private function seedBooks(?User $staff): void
    {
        $classification = Classification::query()->first();
        $classificationDetail = ClassificationDetail::query()->first();
        $warehouse = Warehouse::query()->first();
        $creatorId = $staff?->id;

        if ($classification === null || $classificationDetail === null || $warehouse === null) {
            return;
        }

        for ($i = 1; $i <= 80; $i++) {
            $isReference = $i % 4 === 0;
            $isJournal = $i % 10 === 0;
            $resourceType = $isJournal
                ? ResourceType::JOURNAL->value
                : ($isReference ? ResourceType::REFERENCE->value : ResourceType::TEXTBOOK->value);

            $quantity = $isJournal ? 1 : (($i % 7) + 2);

            Book::query()->updateOrCreate(
                ['registration_number' => sprintf('UTC-PAGE-%04d', $i)],
                [
                    'book_code' => sprintf('PAGE-BOOK-%04d', $i),
                    'title' => sprintf('Sách demo phân trang %03d', $i),
                    'language' => 'Tiếng Việt',
                    'published_year' => 2015 + ($i % 10),
                    'pages' => 120 + ($i % 300),
                    'book_size' => '16x24cm',
                    'price' => 50000 + ($i * 1500),
                    'quantity' => $quantity,
                    'summary' => 'Dữ liệu mẫu phục vụ test phân trang danh mục và phiếu mượn.',
                    'classification_id' => $classification->id,
                    'classification_detail_id' => $classificationDetail->id,
                    'warehouse_id' => $warehouse->id,
                    'resource_type' => $resourceType,
                    'access_mode' => AccessMode::CirculationOnly->value,
                    'params' => [],
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]
            );
        }
    }

    private function seedLibraryCards(?User $staff): void
    {
        $faculty = Faculty::query()->first();
        $period = Period::query()->first();
        $creatorId = $staff?->id;
        $users = User::query()
            ->where('email', 'like', 'demo.user.%@utc.local')
            ->orderBy('id')
            ->get();

        foreach ($users as $idx => $user) {
            $holderType = match ((string) $user->user_type?->value) {
                RoleType::STUDENT->value => LibraryCard::HOLDER_TYPE_STUDENT,
                RoleType::TEACHER->value => LibraryCard::HOLDER_TYPE_TEACHER,
                default => LibraryCard::HOLDER_TYPE_EXTERNAL,
            };

            $issueDate = Carbon::today()->subDays(($idx % 45) + 1);

            LibraryCard::query()->updateOrCreate(
                ['code' => sprintf('CARD-DEMO-%04d', $idx + 1)],
                [
                    'card_number' => sprintf('CARD-DEMO-%04d', $idx + 1),
                    'user_id' => $user->id,
                    'period_id' => $holderType === LibraryCard::HOLDER_TYPE_STUDENT ? $period?->id : null,
                    'holder_type' => $holderType,
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => 'Hà Nội',
                    'faculty_id' => $faculty?->id,
                    'class_code' => $holderType === LibraryCard::HOLDER_TYPE_STUDENT ? sprintf('K67-%02d', ($idx % 20) + 1) : null,
                    'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                    'status' => LibraryCardStatus::ACTIVE->value,
                    'issue_date' => $issueDate->toDateString(),
                    'expiry_date' => $issueDate->copy()->addYear()->toDateString(),
                    'issued_by' => $creatorId,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]
            );
        }
    }

    private function seedLoans(?User $staff): void
    {
        $cards = LibraryCard::query()
            ->where('code', 'like', 'CARD-DEMO-%')
            ->orderBy('id')
            ->take(50)
            ->get();

        $books = Book::query()
            ->whereIn('resource_type', [ResourceType::TEXTBOOK->value, ResourceType::REFERENCE->value, ResourceType::JOURNAL->value])
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->take(40)
            ->get();

        if ($cards->isEmpty() || $books->isEmpty()) {
            return;
        }

        $creatorId = $staff?->id;

        for ($i = 1; $i <= 120; $i++) {
            $card = $cards[($i - 1) % $cards->count()];
            $loanDate = Carbon::today()->subDays(($i % 70) + 1);
            $dueDate = $loanDate->copy()->addDays(10 + ($i % 20));
            $status = match (true) {
                $i % 7 === 0 => Loan::STATUS_OVERDUE,
                $i % 4 === 0 => Loan::STATUS_RETURNED,
                default => Loan::STATUS_BORROWED,
            };
            $returnDate = $status === Loan::STATUS_RETURNED
                ? $dueDate->copy()->subDays($i % 3)->toDateString()
                : null;

            $loan = Loan::query()->updateOrCreate(
                ['loan_code' => sprintf('LD%05d', $i)],
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

            $itemsCount = ($i % 3) + 1;
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
                    'notes' => 'Dữ liệu mẫu pagination',
                ]);
            }
        }
    }
}
