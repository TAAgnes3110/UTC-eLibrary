<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends BaseModel
{
    use SoftDeletes;

    protected $table = 'faculties';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'params',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'params' => 'array',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'faculty_id');
    }
}
