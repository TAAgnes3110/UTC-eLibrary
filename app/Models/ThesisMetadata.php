<?php

namespace App\Models;

use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThesisMetadata extends BaseModel
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'thesis_metadata';

    protected $fillable = [
        'book_id',
        'work_type',
        'degree_program',
        'supervisor_name',
        'supervisor_user_id',
        'defense_year',
        'keywords',
        'abstract_text',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'defense_year' => 'integer',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function supervisorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
