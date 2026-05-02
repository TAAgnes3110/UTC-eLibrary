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
        'parent_id',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(Classification::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Classification::class, 'parent_id');
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function storageCabinets()
    {
        return $this->hasMany(StorageCabinet::class);
    }

    /** Chỉ đầu mục phân loại gốc (000 … 900), không có bản con. */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('parent_id'));
    }
}
