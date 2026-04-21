<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookshelfCellResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'row_index' => $this->row_index,
            'column_index' => $this->column_index,
            'label' => $this->label,
            'current_quantity' => (int) ($this->current_quantity ?? 0),
            'is_active' => $this->is_active,
            'classification_id' => $this->classification_id,
            'classification_detail_id' => $this->classification_detail_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null),
            'classification' => $this->whenLoaded('classification', fn () => $this->classification ? [
                'id' => $this->classification->id,
                'code' => $this->classification->code,
                'name' => $this->classification->name,
            ] : null),
            'classification_detail' => $this->whenLoaded('classificationDetail', fn () => $this->classificationDetail ? [
                'id' => $this->classificationDetail->id,
                'code' => $this->classificationDetail->code,
                'name' => $this->classificationDetail->name,
                'classification_id' => $this->classificationDetail->classification_id,
            ] : null),
            'book_stats' => [
                'title_count' => (int) data_get($this, 'book_stats.title_count', 0),
                'quantity_total' => (int) data_get($this, 'book_stats.quantity_total', 0),
                'available_title_count' => (int) data_get($this, 'book_stats.available_title_count', 0),
                'has_stock' => (bool) data_get($this, 'book_stats.has_stock', false),
            ],
            'params' => $this->params ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
