<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LoanPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Loans/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Loans/Create');
    }

    public function show(int $loan): Response
    {
        return Inertia::render('Admin/Loans/Show', ['loanId' => $loan]);
    }

    public function edit(int $loan): Response
    {
        return Inertia::render('Admin/Loans/Edit', ['loanId' => $loan]);
    }

    public function returnPage(int $loan): Response
    {
        return Inertia::render('Admin/Loans/Return', ['loanId' => $loan]);
    }

    public function renewalRequests(): Response
    {
        return Inertia::render('Admin/Loans/RenewalRequests');
    }

    public function borrowRequests(): Response
    {
        return Inertia::render('Admin/Loans/BorrowRequests');
    }
}
