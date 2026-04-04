<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends BaseModel
{
    use SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'faculty_id',
        'code',
        'name',
        'is_active',
        'params',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'params' => 'array',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
