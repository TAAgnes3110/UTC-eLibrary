<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends BaseModel
{
    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'order_id',
        'item_type',
        'digital_asset_id',
        'quantity',
        'unit_price_vnd_snapshot',
        'line_total_vnd_snapshot',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_vnd_snapshot' => 'integer',
        'line_total_vnd_snapshot' => 'integer',
        'meta' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function digitalAsset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class);
    }
}
