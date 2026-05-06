<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsPost extends BaseModel
{
    use HasAuditFields;

    protected static bool $persistParamsToDatabase = false;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const TYPE_NEWS = 'news';

    public const TYPE_NOTICE = 'notice';

    protected $fillable = [
        'slug',
        'title',
        'content',
        'thumbnail_path',
        'status',
        'type',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_NEWS,
            self::TYPE_NOTICE,
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NewsAttachment::class);
    }
}
