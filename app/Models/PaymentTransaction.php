<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends BaseModel
{
    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'order_id',
        'gateway',
        'status',
        'amount_vnd',
        'currency',
        'gateway_transaction_id',
        'idempotency_key',
        'verified_at',
        'callback_meta',
    ];

    protected $casts = [
        'amount_vnd' => 'integer',
        'verified_at' => 'datetime',
        'callback_meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
