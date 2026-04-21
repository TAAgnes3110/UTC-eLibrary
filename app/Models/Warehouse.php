<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends BaseModel
{
    use HasAuditFields, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'is_active',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Warehouse::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Warehouse::class, 'parent_id');
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function bookshelfCells()
    {
        return $this->hasMany(BookshelfCell::class);
    }
}
