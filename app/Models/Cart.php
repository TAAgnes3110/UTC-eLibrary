<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    public const TYPE_DIGITAL_PURCHASE = 'digital_purchase';

    protected $fillable = [
        'user_id',
        'type',
        'price_locked_until',
    ];

    protected function casts(): array
    {
        return [
            'price_locked_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
