<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CmsPage extends BaseModel
{
    use HasFactory;

    protected $table = 'cms_pages';

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'type',
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
}

