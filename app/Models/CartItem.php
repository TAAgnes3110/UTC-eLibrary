<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    public const ITEM_TYPE_DIGITAL_ASSET_UNLOCK = 'digital_asset_unlock';

    protected $fillable = [
        'cart_id',
        'item_type',
        'digital_asset_id',
        'book_copy_id',
        'quantity',
        'unit_price_vnd_snapshot',
        'line_total_vnd_snapshot',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_vnd_snapshot' => 'integer',
            'line_total_vnd_snapshot' => 'integer',
            'meta' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function digitalAsset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class);
    }
}
