<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'book_copy_id',
        'loan_policy_id',
        'librarian_id',
        'loan_date',
        'due_date',
        'return_date',
        'overdue_days',
        'overdue_fine',
        'status',
        'condition_on_loan',
        'condition_on_return',
        'renewal_count',
        'notes',
        'params',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'overdue_days' => 'integer',
        'overdue_fine' => 'decimal:2',
        'renewal_count' => 'integer',
        'params' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function policy()
    {
        return $this->belongsTo(LoanPolicy::class, 'loan_policy_id');
    }
}

