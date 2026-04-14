<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserProfileUpdateRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        $proofUrl = null;
        if (! empty($this->proof_image_path) && Storage::disk('public')->exists($this->proof_image_path)) {
            $proofUrl = Storage::url($this->proof_image_path);
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'requested_code' => $this->requested_code,
            'requested_faculty_id' => $this->requested_faculty_id,
            'requested_period_id' => $this->requested_period_id,
            'requested_class_code' => $this->requested_class_code,
            'proof_image_path' => $this->proof_image_path,
            'proof_image_url' => $proofUrl,
            'status' => $this->status,
            'reason' => $this->reason,
            'review_note' => $this->review_note,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'applied_at' => $this->applied_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
                'code' => $this->user?->code,
                'class_code' => $this->user?->class_code,
                'faculty_id' => $this->user?->faculty_id,
                'faculty_name' => $this->user?->faculty?->name,
                'period_id' => $this->user?->period_id,
                'period_name' => $this->user?->period?->name,
            ]),
            'requested_faculty' => $this->whenLoaded('requestedFaculty', fn () => $this->requestedFaculty ? [
                'id' => $this->requestedFaculty->id,
                'name' => $this->requestedFaculty->name,
                'code' => $this->requestedFaculty->code,
            ] : null),
            'requested_period' => $this->whenLoaded('requestedPeriod', fn () => $this->requestedPeriod ? [
                'id' => $this->requestedPeriod->id,
                'name' => $this->requestedPeriod->name,
                'code' => $this->requestedPeriod->code,
            ] : null),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ] : null),
        ];
    }
}

