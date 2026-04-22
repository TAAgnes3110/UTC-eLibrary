<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageSlot extends BaseModel
{
    use HasAuditFields, SoftDeletes;

    protected $fillable = [
        'storage_cabinet_id',
        'classification_detail_id',
        'slot_code',
        'slot_name',
        'capacity',
        'current_quantity',
        'is_active',
        'params',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'current_quantity' => 'integer',
        'is_active' => 'boolean',
        'params' => 'array',
    ];

    public function cabinet()
    {
        return $this->belongsTo(StorageCabinet::class, 'storage_cabinet_id');
    }

    public function classificationDetail()
    {
        return $this->belongsTo(ClassificationDetail::class);
    }
}
