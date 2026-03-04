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
        $userType = $this->user_type instanceof \BackedEnum ? $this->user_type->value : $this->user_type;
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $userType,
            'user_type_label' => $this->user_type?->name ?? null,
            'role' => $userType,
            'status' => $this->is_active ? 'active' : 'inactive',
            'faculty_id' => $this->faculty_id,
            'department_id' => $this->department_id,
            'cohort' => $this->cohort,
            'is_active' => $this->is_active,
            'avatar' => $avatar ?: null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'faculty' => $this->whenLoaded('faculty', fn () => $this->faculty ? ['id' => $this->faculty->id, 'name' => $this->faculty->name, 'code' => $this->faculty->code] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? ['id' => $this->department->id, 'name' => $this->department->name, 'faculty_id' => $this->department->faculty_id] : null),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')),

            'library_card' => $this->whenLoaded('libraryCard', fn () => $this->libraryCard ? [
                'id' => $this->libraryCard->id,
                'card_number' => $this->libraryCard->card_number,
                'status' => $this->libraryCard->status,
                'issue_date' => $this->libraryCard->issue_date?->toIso8601String(),
                'expiry_date' => $this->libraryCard->expiry_date?->toIso8601String(),
                'metadata' => $this->libraryCard->metadata,
            ] : null),
            'params' => $this->params ?? [],
        ];
    }
}
