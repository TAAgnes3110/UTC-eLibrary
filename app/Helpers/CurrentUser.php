<?php

namespace App\Helpers;

/**
 * Response Class helper
 */
class CurrentUser
{
    public int $id = 0;
    public string $name = '';
    public int $is_admin = 0;
    public bool $use_hns_sign = false;
    public array $roles = [];
    public array $permissions = [];
    public array $params = [];
    public function __construct($user)
    {
        global $currentPerson;
        $this->id = $user->id;
        $this->name = !empty($user->name) ? $user->name : $currentPerson->name;
        $this->is_admin = $user->is_admin;
        $this->use_hns_sign = $user->use_hns_sign;
        $this->roles = $user->roles instanceof \Illuminate\Support\Collection
            ? $user->roles->pluck('name')->toArray()
            : (array)$user->roles;

        $this->permissions = $user->permissions instanceof \Illuminate\Support\Collection
            ? $user->permissions->pluck('name')->toArray()
            : (array)$user->permissions;

        $this->params = (array)$user->params;
    }
    public function hasRoleOrPermission(string $rolesOrPermission): bool
    {
        global $role_prefix;
        if ((!empty($this->roles) || !empty($this->permissions)) && $rolesOrPermission) {
            $rolesOrPermission = str_replace("role_prefix_", $role_prefix, $rolesOrPermission);
            $rolesOrPermissions = explode("|", $rolesOrPermission);
            if (!empty($this->roles)) {
                foreach ($this->roles as $role) {
                    if (in_array($role, $rolesOrPermissions)) {
                        return true;
                    }
                }
            }
            if (!empty($this->permissions)) {
                foreach ($this->permissions as $permission) {
                    if (in_array($permission, $rolesOrPermissions)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function hasRole(string $role): bool
    {
        global $role_prefix;
        if (!empty($this->roles) && $role) {
            $role = str_replace("role_prefix_", $role_prefix, $role);
            $roles = explode("|", $role);
            foreach ($this->roles as $r) {
                if (in_array($r, $roles)) {
                    return true;
                }
            }
        }
        return false;
    }
    public function hasPermission(string $permission): bool
    {
        global $role_prefix;
        if (!empty($this->permissions) && $permission) {
            $permission = str_replace("role_prefix_", $role_prefix, $permission);
            $permissions = explode("|", $permission);
            foreach ($this->permissions as $p) {
                if (in_array($p, $permissions)) {
                    return true;
                }
            }
        }
        return false;
    }
    public function isAdmin()
    {
        global $currentSystem;
        if ($this->is_admin == 1 && $currentSystem->user_id == $this->id) {
            return  true;
        }
        return false;
    }
    public function isSuperAdmin()
    {
        if ($this->is_admin == 9) {
            return true;
        }
        return false;
    }
    public function isSupporter()
    {
        return $this->hasRole(\App\Enums\RoleType::LIBRARIAN->value) || $this->hasRole(\App\Enums\RoleType::SUPER_ADMIN->value);
    }
}
