<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends BaseModel
{
  protected $fillable = [
    'name',
    'code',
    'address',
    'phone',
    'email',
    'website',
    'contact_person',
    'country',
    'is_active',
    'params',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'params' => 'array',
  ];

  public function books(): HasMany
  {
    return $this->hasMany(Book::class);
  }
}
