<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseModel
{
  protected $fillable = [
    'code',
    'name',
    'description',
    'parent_id',
    'order',
    'is_active',
    'icon',
    'color',
    'params',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'params' => 'array',
  ];

  public function parent(): BelongsTo
  {
    return $this->belongsTo(Category::class, 'parent_id');
  }

  public function children(): HasMany
  {
    return $this->hasMany(Category::class, 'parent_id');
  }

  public function books(): HasMany
  {
    return $this->hasMany(Book::class);
  }
}
