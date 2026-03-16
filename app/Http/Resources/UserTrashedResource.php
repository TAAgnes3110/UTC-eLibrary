<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserTrashedResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'code' => $this->code,
             'deleted_by' => $this->whenLoaded('deletedBy', fn () => $this->deletedBy ? [
                 'id' => $this->deletedBy->id,
                 'name' => $this->deletedBy->name,
                 'email' => $this->deletedBy->email,
             ] : null),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
