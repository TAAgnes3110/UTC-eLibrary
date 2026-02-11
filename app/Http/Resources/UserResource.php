<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'user_type_label' => $this->user_type->name ?? $this->user_type,
            'avatar' => $this->avatar,

            'roles' => $this->whenLoaded('roles', fn() => $this->getRoleNames(), $this->getRoleNames()),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->getAllPermissions()->pluck('name'), $this->getAllPermissions()->pluck('name')),

            'library_card' => $this->libraryCard,
            'params' => $this->params,
        ];
    }
}
