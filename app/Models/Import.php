<?php

namespace App\Models;

use App\Enums\ImportStatus;
use App\Enums\ImportType;
use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'type',
        'status',
        'file_path',
        'total_rows',
        'processed_rows',
        'success_rows',
        'skipped_rows',
        'error_rows',
        'meta',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'type' => ImportType::class,
        'status' => ImportStatus::class,
    ];
}

