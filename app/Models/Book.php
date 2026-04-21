<?php

namespace App\Models;

use App\Enums\AccessMode;
use App\Enums\BookPhysicalCondition;
use App\Enums\BookStatus;
use App\Enums\ResourceType;
use App\Models\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends BaseModel
{
    use HasAuditFields;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleting(function (Book $book): void {
            $book->digitalAssets()->withTrashed()->get()->each->forceDelete();
            ThesisMetadata::withTrashed()->where('book_id', $book->id)->forceDelete();
        });
    }

    protected $appends = [
        'authors_label',
        'publishers_label',
        'status_label',
        'is_available',
    ];

    protected $fillable = [
        'registration_number',
        'book_code',
        'title',
        'sub_title',
        'language',
        'edition',
        'published_year',
        'pages',
        'illustration_pages',
        'book_size',
        'price',
        'quantity',
        'summary',
        'notes',
        'publisher_place',
        'cabinet',
        'shelf',
        'cover_image',
        'classification_id',
        'classification_detail_id',
        'warehouse_id',
        'resource_type',
        'access_mode',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'resource_type' => ResourceType::class,
        'access_mode' => AccessMode::class,
        'published_year' => 'integer',
        'pages' => 'integer',
        'illustration_pages' => 'integer',
        'quantity' => 'integer',
        'price' => 'integer',
    ];

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function classificationDetail()
    {
        return $this->belongsTo(ClassificationDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors')
            ->withTimestamps()
            ->withPivot('order')
            ->orderBy('book_authors.order');
    }

    public function publishers()
    {
        return $this->belongsToMany(Publisher::class, 'book_publishers')
            ->withTimestamps()
            ->withPivot('order')
            ->orderBy('book_publishers.order');
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    public function digitalAssets(): HasMany
    {
        return $this->hasMany(DigitalAsset::class);
    }

    public function thesisMetadata(): HasOne
    {
        return $this->hasOne(ThesisMetadata::class);
    }

    public function availableCopies()
    {
        return $this->hasMany(BookCopy::class)
            ->where('status', BookStatus::AVAILABLE)
            ->whereIn('physical_condition', BookPhysicalCondition::borrowableValues());
    }

    public function getAuthorsLabelAttribute(): string
    {
        if (! $this->relationLoaded('authors')) {
            $this->loadMissing('authors:id,name');
        }

        return $this->authors
            ? $this->authors->pluck('name')->implode('; ')
            : '';
    }

    public function getPublishersLabelAttribute(): string
    {
        if (! $this->relationLoaded('publishers')) {
            $this->loadMissing('publishers:id,name');
        }

        return $this->publishers
            ? $this->publishers->pluck('name')->implode('; ')
            : '';
    }

    public function getIsAvailableAttribute(): bool
    {
        return (int) $this->quantity > 0;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_available ? 'Còn' : 'Hết';
    }
}
