<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DigitalAsset;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DigitalPurchaseCartService
{
    public function __construct(
        private readonly DigitalPaywallService $paywall
    ) {}

    public function getOrCreateDigitalCart(User $user): Cart
    {
        return Cart::query()->firstOrCreate(
            [
                'user_id' => (int) $user->id,
                'type' => Cart::TYPE_DIGITAL_PURCHASE,
            ],
            ['price_locked_until' => null]
        );
    }

    public function countItemsForUser(User $user): int
    {
        $cart = Cart::query()
            ->where('user_id', (int) $user->id)
            ->where('type', Cart::TYPE_DIGITAL_PURCHASE)
            ->first();

        if ($cart === null) {
            return 0;
        }

        return (int) $cart->items()
            ->where('item_type', CartItem::ITEM_TYPE_DIGITAL_ASSET_UNLOCK)
            ->whereNotNull('digital_asset_id')
            ->count();
    }

    /**
     * Đồng bộ snapshot giá trong DB theo cấu hình paywall / thư viện hiện tại (một vòng DB, không query lại toàn bộ).
     *
     * @return Collection<int, CartItem>
     */
    public function listDigitalItemsForUser(User $user, bool $syncPrices = true): Collection
    {
        $cart = Cart::query()
            ->where('user_id', (int) $user->id)
            ->where('type', Cart::TYPE_DIGITAL_PURCHASE)
            ->first();

        if ($cart === null) {
            return collect();
        }

        $items = $cart->items()
            ->where('item_type', CartItem::ITEM_TYPE_DIGITAL_ASSET_UNLOCK)
            ->whereNotNull('digital_asset_id')
            ->with(['digitalAsset' => fn ($q) => $q->with(['book'])])
            ->orderBy('id')
            ->get();

        if ($syncPrices && $items->isNotEmpty()) {
            $items->load(['digitalAsset.paywallSetting']);
            DB::transaction(function () use ($items): void {
                foreach ($items as $item) {
                    $this->syncItemPriceSnapshot($item);
                }
            });
        }

        return $items;
    }

    public function addDigitalItem(User $user, int $digitalAssetId, array $meta = []): CartItem
    {
        $asset = DigitalAsset::query()
            ->whereKey($digitalAssetId)
            ->with(['book.digitalDocumentSubmission', 'paywallSetting'])
            ->firstOrFail();

        if ($this->paywall->userCanDownloadPdf((int) $user->id, $asset)) {
            throw ValidationException::withMessages([
                'digital_asset_id' => __('Bạn đã có quyền tải PDF tài liệu này.'),
            ]);
        }

        $price = $this->paywall->resolvePdfDownloadPriceVnd($asset);
        if ($price <= 0) {
            throw ValidationException::withMessages([
                'digital_asset_id' => __('Tài liệu này không cần thanh toán để tải PDF.'),
            ]);
        }

        $qty = 1;
        $cart = $this->getOrCreateDigitalCart($user);

        return DB::transaction(function () use ($cart, $asset, $price, $qty, $meta): CartItem {
            $cleanMeta = $this->normalizeClientMeta($meta, $asset);

            /** @var CartItem $item */
            $item = CartItem::query()->updateOrCreate(
                [
                    'cart_id' => $cart->id,
                    'digital_asset_id' => $asset->id,
                    'item_type' => CartItem::ITEM_TYPE_DIGITAL_ASSET_UNLOCK,
                ],
                [
                    'book_copy_id' => null,
                    'quantity' => $qty,
                    'unit_price_vnd_snapshot' => $price,
                    'line_total_vnd_snapshot' => $price * $qty,
                    'meta' => $cleanMeta,
                ]
            );

            return $item->load(['digitalAsset' => fn ($q) => $q->with(['book', 'paywallSetting'])]);
        });
    }

    public function removeDigitalItem(User $user, int $digitalAssetId): void
    {
        $cart = Cart::query()
            ->where('user_id', (int) $user->id)
            ->where('type', Cart::TYPE_DIGITAL_PURCHASE)
            ->first();
        if ($cart === null) {
            return;
        }

        CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('digital_asset_id', $digitalAssetId)
            ->where('item_type', CartItem::ITEM_TYPE_DIGITAL_ASSET_UNLOCK)
            ->delete();
    }

    /**
     * @param  list<int>  $digitalAssetIds
     */
    public function removeDigitalItems(User $user, array $digitalAssetIds): void
    {
        $cart = Cart::query()
            ->where('user_id', (int) $user->id)
            ->where('type', Cart::TYPE_DIGITAL_PURCHASE)
            ->first();
        if ($cart === null) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $digitalAssetIds))));
        if ($ids === []) {
            return;
        }

        CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('item_type', CartItem::ITEM_TYPE_DIGITAL_ASSET_UNLOCK)
            ->whereIn('digital_asset_id', $ids)
            ->delete();
    }

    private function syncItemPriceSnapshot(CartItem $item): void
    {
        $asset = $item->digitalAsset;
        if ($asset === null) {
            return;
        }

        if (! $asset->relationLoaded('paywallSetting')) {
            $asset->load('paywallSetting');
        }
        $price = $this->paywall->resolvePdfDownloadPriceVnd($asset);
        $qty = max(1, (int) $item->quantity);
        $line = $price * $qty;

        if ((int) $item->unit_price_vnd_snapshot === $price && (int) $item->line_total_vnd_snapshot === $line) {
            return;
        }

        $item->forceFill([
            'unit_price_vnd_snapshot' => $price,
            'line_total_vnd_snapshot' => $line,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function normalizeClientMeta(array $meta, DigitalAsset $asset): array
    {
        $book = $asset->relationLoaded('book') ? $asset->book : $asset->book()->first();

        $bookId = isset($meta['book_id']) ? (int) $meta['book_id'] : (int) $asset->book_id;
        $bookTitle = isset($meta['book_title']) && is_string($meta['book_title']) ? trim($meta['book_title']) : (string) ($book?->title ?? '');
        $fileName = isset($meta['file_name']) && is_string($meta['file_name']) ? trim($meta['file_name']) : (string) ($asset->original_name ?? '');
        $cover = isset($meta['cover_image']) && is_string($meta['cover_image']) ? trim($meta['cover_image']) : '';

        return [
            'book_id' => $bookId > 0 ? $bookId : null,
            'book_title' => $bookTitle !== '' ? $bookTitle : null,
            'file_name' => $fileName !== '' ? $fileName : null,
            'cover_image' => $cover !== '' ? $cover : null,
        ];
    }
}
