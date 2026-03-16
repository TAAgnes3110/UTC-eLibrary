<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;

class Warehouse extends BaseModel
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
}

