<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends BaseModel
{
    use SoftDeletes;

    protected $table = 'books';

    protected $fillable = [
        'type',
        'title',
        'isbn',
        'classification_code',
        'classification_detail',
        'language',
        'edition',
        'category_id',
        'faculty_id',
        'publisher_id',
        'publication_place',
        'published_year',
        'total_pages',
        'book_size',
        'volume_number',
        'price',
        'notes',
        'status',
        'total_copies',
        'available_copies',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'price' => 'decimal:2',
        'type' => \App\Enums\BookType::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author')
            ->using(BookAuthor::class)
            ->withPivot('role', 'order')
            ->orderBy('order')
            ->withTimestamps();
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies(): HasMany
    {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function updateStatistics(): void
    {
        $this->total_copies = $this->copies()->count();
        $this->available_copies = $this->availableCopies()->count();
        $this->save();
    }
}
