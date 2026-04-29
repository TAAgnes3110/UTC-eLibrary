<?php

namespace App\Models;

use App\Enums\LoanItemCondition;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanBorrowRequestItem extends BaseModel
{
    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'borrow_request_id',
        'book_id',
        'quantity',
        'condition_on_loan',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'condition_on_loan' => LoanItemCondition::class,
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(LoanBorrowRequest::class, 'borrow_request_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}
