<?php

namespace App\Models;

use App\Helpers\FileHelpers;
use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'original_name',
        'mime',
        'byte_size',
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
        'embargo_until' => 'date',
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (DigitalAsset $asset): void {
            FileHelpers::deleteIfExists($asset->path, $asset->storage_disk ?: 'public');
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
}
