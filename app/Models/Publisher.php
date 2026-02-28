<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publisher extends BaseModel
{
  use SoftDeletes;

  protected $table = 'publishers';
  public const DEFAULT_PUBLISHER_NAME = 'Trường đại học Giao thông vận tải';

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

  public static function getDefaultPublisher(): ?self
  {
    return static::query()
      ->where('name', self::DEFAULT_PUBLISHER_NAME)
      ->first();
  }

  public static function getOrCreateDefaultPublisher(): self
  {
    $publisher = static::getDefaultPublisher();
    if ($publisher) {
      return $publisher;
    }
    return static::create([
      'name' => self::DEFAULT_PUBLISHER_NAME,
      'code' => 'UTT',
      'country' => 'Việt Nam',
      'is_active' => true,
    ]);
  }
}
