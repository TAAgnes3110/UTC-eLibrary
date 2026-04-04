<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryCardPayment extends Model
{
    protected $table = 'library_card_payments';

    protected $fillable = [
        'library_card_id',
        'payment_status',
        'payment_amount',
        'paid_at',
        'payment_method',
        'receipt_number',
        'payment_collected_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'payment_amount' => 'decimal:2',
        ];
    }

    public function libraryCard(): BelongsTo
    {
        return $this->belongsTo(LibraryCard::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_collected_by');
    }
}
