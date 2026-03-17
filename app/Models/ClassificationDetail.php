<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;

class ClassificationDetail extends BaseModel
{
    use HasAuditFields;

    protected $fillable = [
        'code',
        'name',
        'classification_id',
        'parent_id',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function parent()
    {
        return $this->belongsTo(ClassificationDetail::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ClassificationDetail::class, 'parent_id');
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}

