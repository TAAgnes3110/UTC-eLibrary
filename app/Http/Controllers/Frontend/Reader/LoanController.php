<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $loans = Loan::where('user_id', $request->user()->id)
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

        return Inertia::render('Reader/Loans/Index', ['loans' => $loans]);
    }
}
