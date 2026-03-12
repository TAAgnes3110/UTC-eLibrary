<?php

namespace App\Models;

class Publisher extends BaseModel
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
        return $this->belongsToMany(Book::class, 'book_publishers')
            ->withTimestamps()
            ->withPivot('order');
    }
}

