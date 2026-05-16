<?php

namespace App\Models;

use App\Helpers\FileHelpers;
use App\Models\Traits\HasAuditFields;
use App\Services\DigitalAssetPreviewDisplayService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalAsset extends BaseModel
{
    use HasAuditFields;
    use SoftDeletes;

    protected $fillable = [
        'book_id',
        'version',
        'is_primary',
        'storage_disk',
        'path',
        'preview_path',
        'preview_page_count',
        'preview_generated_at',
        'preview_display',
        'original_name',
        'mime',
        'byte_size',
        'view_count',
        'download_count',
        'checksum_sha256',
        'visibility',
        'embargo_until',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'version' => 'integer',
        'is_primary' => 'boolean',
        'byte_size' => 'integer',
        'view_count' => 'integer',
        'download_count' => 'integer',
        'embargo_until' => 'date',
        'preview_page_count' => 'integer',
        'preview_generated_at' => 'datetime',
        'preview_display' => 'array',
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (DigitalAsset $asset): void {
            $disk = $asset->storage_disk ?: FileHelpers::digitalAssetsDisk();
            FileHelpers::deleteIfExists($asset->path, $disk);
            FileHelpers::deleteIfExists($asset->preview_path, $disk);
            if (is_array($asset->preview_display)) {
                app(DigitalAssetPreviewDisplayService::class)->deleteDisplayFiles($asset);
            }
        });
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function accessSessions(): HasMany
    {
        return $this->hasMany(DigitalAccessSession::class);
    }

    public function paywallSetting(): HasOne
    {
        return $this->hasOne(DigitalAssetPaywallSetting::class);
    }

    public function fullAccessEntitlements(): HasMany
    {
        return $this->hasMany(DigitalAssetPdfDownloadEntitlement::class);
    }
}
