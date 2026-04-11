<?php

namespace App\Models;

use App\Enums\LoanItemCondition;
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
    ];

    protected $casts = [
        'quantity' => 'integer',
        'condition_on_loan' => LoanItemCondition::class,
        'condition_on_return' => LoanItemCondition::class,
        'fine_amount' => 'decimal:2',
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
