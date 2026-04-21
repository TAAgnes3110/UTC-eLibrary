<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookshelfCell extends BaseModel
{
    use HasAuditFields, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'row_index',
        'column_index',
        'label',
        'current_quantity',
        'classification_id',
        'classification_detail_id',
        'is_active',
        'params',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'is_active' => 'boolean',
        'params' => 'array',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function classificationDetail()
    {
        return $this->belongsTo(ClassificationDetail::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }
}
