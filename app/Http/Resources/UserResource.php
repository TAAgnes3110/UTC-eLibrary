<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $avatar = $this->avatar;
        if (!empty($avatar) && !str_starts_with($avatar, 'http')) {
            if (Storage::disk('public')->exists($avatar)) {
                $avatar = asset('storage/' . ltrim($avatar, '/'));
            } else {
                $avatar = null;
            }
        }
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'user_type_label' => $this->user_type->name ?? $this->user_type,
            'avatar' => $avatar ?: null,

            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames(), $this->getRoleNames()),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->getAllPermissions()->pluck('name'), $this->getAllPermissions()->pluck('name')),

            'library_card' => $this->libraryCard,
            'params' => $this->params,
        ];
    }
}
