<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends BaseModel
{
    protected $table = 'customers';
    public static string $tableName = 'customers';
    public $primaryKey = 'id';

    protected $fillable = [
        'name',
        'code',
        'params',
        'status',
    ];

    protected $casts = [
        'params' => 'object',
    ];
}
