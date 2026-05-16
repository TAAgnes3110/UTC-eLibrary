<?php

namespace App\Http\Controllers\Api\Me;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DigitalPaymentOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeDigitalOrderController extends Controller
{
    public function __construct(
        private readonly DigitalPaymentOrderService $digitalPaymentOrders
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,paid,cancelled,failed,expired'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $this->digitalPaymentOrders->pruneStalePendingDigitalOrders();

        $perPage = min(max((int) ($validated['per_page'] ?? 20), 1), 100);
        $search = trim((string) ($validated['search'] ?? ''));

        $query = $this->baseQuery((int) $user->id)
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('public_id', 'like', "%{$search}%")
                        ->orWhere('merchant_reference', 'like', "%{$search}%");
                });
            })
            ->when(! empty($validated['status']), fn (Builder $q) => $q->where('status', $validated['status']));

        $sort = $validated['sort'] ?? 'newest';
        if ($sort === 'oldest') {
            $query->orderBy('created_at')->orderBy('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success($paginator->through(fn (Order $order) => $this->mapListRow($order)));
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập.'), 401);
        }

        $this->digitalPaymentOrders->pruneStalePendingDigitalOrders();

        $userId = (int) $user->id;
        $base = Order::query()
            ->where('user_id', $userId)
            ->where('type', 'digital_purchase');

        $total = (clone $base)->count();
        $paidCount = (clone $base)->where('status', 'paid')->count();
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $totalSpentVnd = (int) (clone $base)->where('status', 'paid')->sum('total_vnd_snapshot');

        return ApiResponse::success([
            'total_orders' => $total,
            'paid_count' => $paidCount,
            'pending_count' => $pendingCount,
            'total_spent_vnd' => $totalSpentVnd,
        ]);
    }

    private function baseQuery(int $userId): Builder
    {
        return Order::query()
            ->with(['items.digitalAsset.book'])
            ->where('user_id', $userId)
            ->where('type', 'digital_purchase');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapListRow(Order $order): array
    {
        $items = $order->items;
        $first = $items->first();
        $firstTitle = 'Tài liệu số';
        if ($first !== null) {
            $book = $first->digitalAsset?->book;
            $firstTitle = (string) (data_get($first->meta, 'book_title') ?: $book?->title ?: 'Tài liệu số');
        }
        $itemCount = $items->count();
        $productSummary = $itemCount <= 1
            ? $firstTitle
            : $firstTitle.' và '.($itemCount - 1).' tài liệu khác';

        $status = (string) $order->status;
        $pendingMaxDays = $this->digitalPaymentOrders->pendingMaxAgeDays();
        $autoRemoveAt = $status === Order::STATUS_PENDING && $order->created_at !== null
            ? $order->created_at->copy()->addDays($pendingMaxDays)->toIso8601String()
            : null;

        return [
            'public_id' => (string) $order->public_id,
            'status' => $status,
            'status_label' => $this->orderStatusLabel($status),
            'fulfillment_label' => $status === 'paid' ? 'Hoàn thành' : ($status === 'pending' ? 'Chờ xử lý' : $this->orderStatusLabel($status)),
            'payment_label' => $status === 'paid' ? 'Đã thanh toán' : ($status === 'pending' ? 'Chờ thanh toán' : $this->orderStatusLabel($status)),
            'total_vnd' => (int) $order->total_vnd_snapshot,
            'currency' => (string) $order->currency,
            'item_count' => $itemCount,
            'product_summary' => $productSummary,
            'merchant_reference' => (string) $order->merchant_reference,
            'created_at' => $order->created_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'can_pay' => $status === Order::STATUS_PENDING,
            'can_cancel' => $status === Order::STATUS_PENDING,
            'auto_remove_at' => $autoRemoveAt,
            'pending_max_age_days' => $pendingMaxDays,
        ];
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ thanh toán',
            'paid' => 'Đã thanh toán',
            'cancelled' => 'Đã hủy',
            'failed' => 'Thanh toán thất bại',
            'expired' => 'Hết hạn',
            default => $status,
        };
    }
}
