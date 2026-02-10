<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Enums\RoleType;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'code',
        'phone',
        'user_type',
        'avatar',
        'date_of_birth',
        'gender',
        'address',
        'is_active',
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
            'user_type' => RoleType::class,
            'is_active' => 'boolean',
        ];
    }
    public function libraryCard()
    {
        return $this->hasOne(LibraryCard::class);
    }
    public function fines()
    {
        return $this->hasMany(Fine::class);
    }
    public function scopeDuplicate($query, array $data, ?int $excludeId = null)
    {
        return $query->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($data) {
                $q->where('email', $data['email'])
                    ->orWhere('code', $data['code'])
                    ->when(!empty($data['phone']), fn($q) => $q->orWhere('phone', $data['phone']));
            });
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getPermissionNames()
        ];
    }
}
