<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends BaseModel implements JWTSubject, AuthenticatableContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Authenticatable, HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

    public static string $tableName = 'users';
    protected $table = 'users';
    public $primaryKey = 'id';
    protected $fillable = [
        'name',
        'email',
        'password',
        'code',
        'phone',
        'card_number',
        'role',
        'customer_id',
        'params',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => \App\Enums\RoleType::class,
            'params' => 'object',
        ];
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
}
