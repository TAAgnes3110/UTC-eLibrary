<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends BaseModel
{
    public const TYPE_DIGITAL_PURCHASE = 'digital_purchase';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    public const GATEWAY_SEPAY = 'sepay';

    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'public_id',
        'user_id',
        'type',
        'status',
        'subtotal_vnd_snapshot',
        'total_vnd_snapshot',
        'currency',
        'price_locked_until',
        'paid_at',
        'gateway',
        'merchant_reference',
        'gateway_init_payload',
    ];

    protected $casts = [
        'subtotal_vnd_snapshot' => 'integer',
        'total_vnd_snapshot' => 'integer',
        'price_locked_until' => 'datetime',
        'paid_at' => 'datetime',
        'gateway_init_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
