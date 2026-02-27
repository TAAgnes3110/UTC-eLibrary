<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends BaseModel
{
    use SoftDeletes;

    protected $table = 'authors';

    protected $fillable = [
        'name',
        'nationality',
        'tieu_su',
        'birth_date',
        'params',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'params' => 'array',
    ];

    public function scopeDuplicate(Builder $query, array $data, ?int $excludeId = null): Builder
    {
        $q = $query->where('name', $data['name'] ?? '');
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q;
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_author')
            ->using(BookAuthor::class)
            ->withPivot('role', 'order')
            ->orderBy('order')
            ->withTimestamps();
    }
}
