<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;

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

    public function details()
    {
        return $this->hasMany(ClassificationDetail::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
