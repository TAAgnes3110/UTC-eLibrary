<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property-read Order|null $order */
class DigitalAssetPdfDownloadEntitlement extends BaseModel
{
    protected static bool $persistParamsToDatabase = false;

    protected $table = 'digital_asset_pdf_download_entitlements';

    protected $fillable = [
        'user_id',
        'digital_asset_id',
        'order_id',
        'granted_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function digitalAsset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
