<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Arr;

class ReaderService
{
    public function dashboardData(User $user): array
    {
        $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->count();
        $overdueCount = Loan::where('user_id', $user->id)->where('status', 'active')->where('due_date', '<', now()->toDateString())->count();
        $hasCard = $user->libraryCard()->exists();

        return [
            'stats' => [
                'activeLoans' => $activeLoans,
                'overdueCount' => $overdueCount,
                'hasCard' => $hasCard,
            ],
        ];
    }

    public function loansData(User $user): array
    {
        $loans = Loan::where('user_id', $user->id)
            ->with(['bookCopy.book'])
            ->orderByDesc('loan_date')
            ->get()
            ->map(function ($loan) {
                $copy = $loan->bookCopy;
                $book = $copy?->book;
                $dueDate = $loan->due_date?->format('d/m/Y');
                $isOverdue = $loan->status === 'active' && $loan->due_date && $loan->due_date->isPast();
                return [
                    'id' => $loan->id,
                    'book_title' => $book?->title ?? '—',
                    'barcode' => $copy?->barcode ?? $copy?->id,
                    'loan_date' => $loan->loan_date?->format('d/m/Y'),
                    'due_date' => $dueDate,
                    'status' => $loan->status,
                    'is_overdue' => $isOverdue,
                ];
            });

        return ['loans' => $loans];
    }

    public function cardData(User $user): array
    {
        $card = $user->libraryCard;
        $cardData = null;
        if ($card) {
            $cardData = [
                'card_number' => $card->card_number,
                'status' => $card->status,
                'issue_date' => $card->issue_date?->format('d/m/Y'),
                'expiry_date' => $card->expiry_date?->format('d/m/Y'),
                'faculty' => Arr::get($card->metadata ?? [], 'faculty'),
                'class' => Arr::get($card->metadata ?? [], 'class'),
            ];
        }
        return ['card' => $cardData];
    }
}
