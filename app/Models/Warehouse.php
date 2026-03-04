<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends BaseModel
{
    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
