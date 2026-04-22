<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorageSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'storage_cabinet_id' => $this->storage_cabinet_id,
            'classification_detail_id' => $this->classification_detail_id,
            'slot_code' => $this->slot_code,
            'slot_name' => $this->slot_name,
            'capacity' => (int) $this->capacity,
            'current_quantity' => (int) $this->current_quantity,
            'is_active' => (bool) $this->is_active,
            'cabinet' => $this->whenLoaded('cabinet', fn () => $this->cabinet ? [
                'id' => $this->cabinet->id,
                'warehouse_id' => $this->cabinet->warehouse_id,
                'classification_id' => $this->cabinet->classification_id,
                'code' => $this->cabinet->code,
                'name' => $this->cabinet->name,
                'warehouse' => $this->cabinet->relationLoaded('warehouse') && $this->cabinet->warehouse ? [
                    'id' => $this->cabinet->warehouse->id,
                    'code' => $this->cabinet->warehouse->code,
                    'name' => $this->cabinet->warehouse->name,
                ] : null,
            ] : null),
            'classification_detail' => $this->whenLoaded('classificationDetail', fn () => $this->classificationDetail ? [
                'id' => $this->classificationDetail->id,
                'code' => $this->classificationDetail->code,
                'name' => $this->classificationDetail->name,
                'classification_id' => $this->classificationDetail->classification_id,
            ] : null),
            'params' => $this->params ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
