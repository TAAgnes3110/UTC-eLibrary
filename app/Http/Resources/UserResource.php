<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $avatar = $this->avatar;
        if (! empty($avatar) && ! str_starts_with($avatar, 'http')) {
            // Avoid per-row filesystem exists() checks in large admin tables.
            $avatar = Storage::url($avatar);
        }
        $userType = $this->user_type instanceof \BackedEnum ? $this->user_type->value : $this->user_type;
        $status = 'active';
        if ($this->trashed()) {
            $status = 'inactive';
        } elseif (! $this->is_active) {
            $status = 'blocked';
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $userType,
            'user_type_label' => $this->user_type?->name ?? null,
            'role' => $userType,
            'status' => $status,
            'faculty_id' => $this->faculty_id,
            'department_id' => $this->department_id,
            'cohort' => $this->cohort,
            'period_id' => $this->period_id,
            'class_code' => $this->class_code,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'avatar' => $avatar ?: null,
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
                'email' => $this->createdBy->email,
            ] : null),
            'updated_by' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
                'email' => $this->updatedBy->email,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'faculty' => $this->whenLoaded('faculty', fn () => $this->faculty ? ['id' => $this->faculty->id, 'name' => $this->faculty->name, 'code' => $this->faculty->code] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? ['id' => $this->department->id, 'name' => $this->department->name, 'faculty_id' => $this->department->faculty_id] : null),
            'period' => $this->whenLoaded('period', fn () => $this->period ? [
                'id' => $this->period->id,
                'code' => $this->period->code,
                'name' => $this->period->name,
                'start_year' => $this->period->start_year,
                'end_year' => $this->period->end_year,
            ] : null),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')),

            'library_card' => $this->whenLoaded(
                'libraryCard',
                fn () => $this->libraryCard ? new LibraryCardResource($this->libraryCard) : null
            ),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'deleted_by' => $this->whenLoaded('deletedBy', fn () => $this->deletedBy ? [
                'id' => $this->deletedBy->id,
                'name' => $this->deletedBy->name,
                'email' => $this->deletedBy->email,
            ] : null),
        ];
    }
}
