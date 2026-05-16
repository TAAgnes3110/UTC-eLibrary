<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAssetPaywallSetting extends BaseModel
{
    use HasAuditFields;

    protected static bool $persistParamsToDatabase = false;

    protected $table = 'digital_asset_paywall_settings';

    protected $fillable = [
        'digital_asset_id',
        'is_paywall_enabled',
        'pdf_download_price_vnd',
        'currency',
        'internal_note',
    ];

    protected $casts = [
        'is_paywall_enabled' => 'boolean',
        'pdf_download_price_vnd' => 'integer',
    ];

    public function digitalAsset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class);
    }
}
