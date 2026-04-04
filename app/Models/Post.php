<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends BaseModel
{
    use HasFactory;

    protected $table = 'posts';

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'type',
        'is_published',
        'published_at',
        'author_id',
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
}
