<?php

namespace App\Helpers;

use App\Enums\RoleType;

class CurrentUser
{
    public int $id = 0;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $code = '';
    public string $avatar = '';
    public string $user_type = '';
    public int $is_admin = 0;
    public array $roles = [];
    public array $permissions = [];

    public function __construct($user)
    {
        $this->id = $user->id;
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->code = $user->code ?? '';
        $this->avatar = $user->avatar ?? '';

        $userType = $user->user_type ?? '';
        $this->user_type = $userType instanceof RoleType ? $userType->value : (string)$userType;

        $this->is_admin = match ($this->user_type) {
            RoleType::SUPER_ADMIN->value => 9,
            RoleType::ADMIN->value       => 1,
            default                      => 0,
        };

        if (method_exists($user, 'getRoleNames')) {
            $this->roles = $user->getRoleNames()->toArray();
        } elseif (isset($user->roles)) {
            $this->roles = is_array($user->roles) ? $user->roles : (array)$user->roles;
        }

        if (method_exists($user, 'getAllPermissions')) {
            $this->permissions = $user->getAllPermissions()->pluck('name')->toArray();
        } elseif (isset($user->permissions)) {
            $this->permissions = is_array($user->permissions) ? $user->permissions : (array)$user->permissions;
        }
    }

    public function isAdmin(): bool
    {
        return $this->is_admin >= 1;
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_admin === 9;
    }

    public function isLibrarian(): bool
    {
        return $this->user_type === RoleType::LIBRARIAN->value || $this->hasRole(RoleType::LIBRARIAN->value);
    }

    public function isMember(): bool
    {
        return $this->user_type === RoleType::MEMBER->value;
    }

    /**
     * Nhân viên thư viện (Thủ thư, Admin, SuperAdmin)
     */
    public function isStaff(): bool
    {
        return in_array($this->user_type, RoleType::staffRoles());
    }

    public function hasRoleOrPermission(string $rolesOrPermission): bool
    {
        if (empty($rolesOrPermission)) return false;
        if ($this->isSuperAdmin()) return true;

        $items = explode('|', $rolesOrPermission);
        foreach ($items as $item) {
            if (in_array($item, $this->roles) || in_array($item, $this->permissions)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole(string $role): bool
    {
        if (empty($role)) return false;
        if ($this->isSuperAdmin()) return true;

        foreach ($this->roles as $r) {
            if (in_array($r, explode('|', $role))) return true;
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($permission)) return false;
        if ($this->isSuperAdmin()) return true;

        foreach ($this->permissions as $p) {
            if (in_array($p, explode('|', $permission))) return true;
        }
        return false;
    }
}
