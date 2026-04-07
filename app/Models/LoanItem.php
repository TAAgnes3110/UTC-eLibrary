<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends BaseModel
{
    protected $fillable = [
        'loan_id',
        'book_id',
        'quantity',
        'condition_on_loan',
        'condition_on_return',
        'fine_amount',
        'notes',
        'params',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'fine_amount' => 'decimal:2',
        'params' => 'array',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
