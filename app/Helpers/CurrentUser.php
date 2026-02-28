<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\RoleType;
use App\Models\User;

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
    private User $user;
    private bool $rolesAndPermissionsLoaded = false;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->id = $user->id;
        $this->name = (string) ($user->name ?? '');
        $this->email = (string) ($user->email ?? '');
        $this->phone = (string) ($user->phone ?? '');
        $this->code = (string) ($user->code ?? '');
        $this->avatar = (string) ($user->avatar ?? '');

        $userType = $user->user_type ?? null;
        $this->user_type = $userType instanceof RoleType ? $userType->value : (string) $userType;

        $this->is_admin = match ($this->user_type) {
            RoleType::SUPER_ADMIN->value => 9,
            RoleType::ADMIN->value => 1,
            default => 0,
        };
    }

    /**
     * Load Spatie roles & permissions một lần khi cần (tránh query mỗi request nếu không dùng).
     *
     * @return void
     */
    private function ensureRolesAndPermissions(): void
    {
        if ($this->rolesAndPermissionsLoaded) {
            return;
        }
        $this->rolesAndPermissionsLoaded = true;

        if (method_exists($this->user, 'getRoleNames')) {
            $this->roles = $this->user->getRoleNames()->toArray();
        }
        if (method_exists($this->user, 'getPermissionNames')) {
            $this->permissions = $this->user->getPermissionNames()->toArray();
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
        if ($this->user_type === RoleType::LIBRARIAN->value) {
            return true;
        }
        return $this->hasRole(RoleType::LIBRARIAN->value);
    }
    public function isMember(): bool
    {
        return $this->user_type === RoleType::MEMBER->value;
    }

    /** Nhân viên thư viện: Thủ thư, Admin, SuperAdmin (theo user_type, không query). */
    public function isStaff(): bool
    {
        return in_array($this->user_type, RoleType::staffRoles(), true);
    }

    /**
     * Kiểm tra có một trong các role hoặc permission (chuỗi dạng "ROLE_A|permission_b").
     * SuperAdmin luôn true. Gọi lần đầu sẽ load roles/permissions từ DB.
     *
     * @param string $rolesOrPermission Danh sách role hoặc permission, phân tách bằng |.
     * @return bool
     */
    public function hasRoleOrPermission(string $rolesOrPermission): bool
    {
        if ($rolesOrPermission === '') {
            return false;
        }
        if ($this->isSuperAdmin()) {
            return true;
        }
        $this->ensureRolesAndPermissions();
        $items = explode('|', $rolesOrPermission);
        foreach ($items as $item) {
            $item = trim($item);
            if ($item !== '' && (in_array($item, $this->roles, true) || in_array($item, $this->permissions, true))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Kiểm tra có một trong các role (chuỗi dạng "ROLE_A|ROLE_B"). SuperAdmin luôn true.
     *
     * @param string $role Danh sách role phân tách bằng |.
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if ($role === '') {
            return false;
        }
        if ($this->isSuperAdmin()) {
            return true;
        }
        $this->ensureRolesAndPermissions();
        $allowed = explode('|', $role);
        foreach ($this->roles as $r) {
            if (in_array($r, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Kiểm tra có một trong các permission (chuỗi dạng "a|b|c"). SuperAdmin luôn true.
     *
     * @param string $permission Danh sách permission phân tách bằng |.
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if ($permission === '') {
            return false;
        }
        if ($this->isSuperAdmin()) {
            return true;
        }
        $this->ensureRolesAndPermissions();
        $allowed = explode('|', $permission);
        foreach ($this->permissions as $p) {
            if (in_array($p, $allowed, true)) {
                return true;
            }
        }
        return false;
    }
}
