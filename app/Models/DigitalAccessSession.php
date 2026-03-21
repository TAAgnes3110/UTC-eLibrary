<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAccessSession extends Model
{
    protected $fillable = [
        'user_id',
        'digital_asset_id',
        'granted_at',
        'expires_at',
        'download_count',
        'max_downloads',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'download_count' => 'integer',
        'max_downloads' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function digitalAsset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class);
    }
}
