<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsAttachment extends BaseModel
{
    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'news_post_id',
        'storage_disk',
        'file_path',
        'original_name',
        'mime',
        'byte_size',
    ];

    protected $casts = [
        'byte_size' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }
}
