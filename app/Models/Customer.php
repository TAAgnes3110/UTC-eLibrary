<?php

namespace App\Models;

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
        'params' => 'array',
    ];
}
