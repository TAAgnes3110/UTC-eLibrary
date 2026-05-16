<?php

namespace App\Http\Resources;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Một dòng giỏ thanh toán tài liệu số — giá lấy từ snapshot DB (đã đồng bộ trước khi serialize). */
class DigitalPurchaseCartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CartItem $item */
        $item = $this->resource;
        $meta = is_array($item->meta) ? $item->meta : [];
        $asset = $item->digitalAsset;

        $bookId = isset($meta['book_id']) ? (int) $meta['book_id'] : (int) ($asset?->book_id ?? 0);
        $bookTitle = isset($meta['book_title']) && is_string($meta['book_title']) ? trim($meta['book_title']) : '';
        $fileName = isset($meta['file_name']) && is_string($meta['file_name']) ? trim($meta['file_name']) : '';
        $cover = isset($meta['cover_image']) && is_string($meta['cover_image']) ? trim($meta['cover_image']) : '';

        if ($fileName === '' && $asset !== null) {
            $fileName = trim((string) ($asset->original_name ?? ''));
        }
        if ($bookTitle === '' && $asset?->relationLoaded('book') && $asset->book !== null) {
            $bookTitle = trim((string) ($asset->book->title ?? ''));
        }

        $price = max(0, (int) ($item->unit_price_vnd_snapshot ?? 0));

        $paywallEnabled = null;
        if ($asset !== null) {
            if ($asset->relationLoaded('paywallSetting') || $asset->paywallSetting !== null) {
                $paywallEnabled = $price > 0;
            } elseif ($price > 0) {
                $paywallEnabled = true;
            } else {
                $paywallEnabled = false;
            }
        }

        return [
            'digital_asset_id' => (int) $item->digital_asset_id,
            'book_id' => $bookId > 0 ? $bookId : null,
            'book_title' => $bookTitle !== '' ? $bookTitle : null,
            'file_name' => $fileName !== '' ? $fileName : null,
            'cover_image' => $cover !== '' ? $cover : null,
            'price_vnd' => $price,
            'paywall_enabled' => $paywallEnabled,
        ];
    }
}
