<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class LibraryService extends BaseModel
{
    use HasFactory;

    protected $table = 'library_services';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'params',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'params' => 'array',
        ];
    }
}

