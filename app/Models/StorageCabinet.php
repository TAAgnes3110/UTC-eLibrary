<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageCabinet extends BaseModel
{
    use HasAuditFields, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'classification_id',
        'code',
        'name',
        'capacity_total',
        'current_quantity',
        'is_active',
        'params',
    ];

    protected $casts = [
        'capacity_total' => 'integer',
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

    public function slots()
    {
        return $this->hasMany(StorageSlot::class);
    }
}
