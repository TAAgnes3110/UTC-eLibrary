<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Builder;

class Classification extends BaseModel
{
    use HasAuditFields;

    protected $fillable = [
        'code',
        'name',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function storageCabinets()
    {
        return $this->hasMany(StorageCabinet::class);
    }

    /** Đầu mục phân loại gốc (000 … 900) — schema không còn parent_id. */
    public function scopeRoots(Builder $query): Builder
    {
        return $query;
    }
}
