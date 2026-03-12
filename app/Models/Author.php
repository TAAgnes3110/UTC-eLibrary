<?php

namespace App\Models;

class Author extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_authors')
            ->withTimestamps()
            ->withPivot('order');
    }
}

