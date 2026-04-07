<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteContent extends BaseModel
{
    public const KIND_PAGE = 'page';

    public const KIND_POST = 'post';

    public const KIND_SERVICE = 'service';

    protected $table = 'site_contents';

    protected $fillable = [
        'kind',
        'slug',
        'title',
        'excerpt',
        'content',
        'subtype',
        'author_id',
        'is_published',
        'published_at',
        'params',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'params' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
