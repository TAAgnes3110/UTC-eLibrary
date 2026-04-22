<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageCabinetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'classification_id' => $this->classification_id,
            'code' => $this->code,
            'name' => $this->name,
            'capacity_total' => (int) $this->capacity_total,
            'current_quantity' => (int) $this->current_quantity,
            'is_active' => (bool) $this->is_active,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
                'note' => data_get($this->warehouse->params, 'note'),
                'params' => $this->warehouse->params ?? [],
            ] : null),
            'classification' => $this->whenLoaded('classification', fn () => $this->classification ? [
                'id' => $this->classification->id,
                'code' => $this->classification->code,
                'name' => $this->classification->name,
            ] : null),
            'slots' => StorageSlotResource::collection($this->whenLoaded('slots')),
            'params' => $this->params ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
