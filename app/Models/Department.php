<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends BaseModel
{
  use SoftDeletes;

  protected $fillable = [
    'code',
    'name',
    'description',
    'faculty_id',
  ];

  public function faculty()
  {
    return $this->belongsTo(Faculty::class);
  }

  // public function readers(): HasMany
  // {
  //   return $this->hasMany(Reader::class);
  // }
}
