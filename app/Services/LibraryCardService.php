<?php

namespace App\Services;

use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class LibraryCardService
{
    private const PER_PAGE = 30;

    /** @var list<string> */
    private const PAYMENT_ATTRIBUTE_KEYS = [
        'payment_status',
        'payment_amount',
        'paid_at',
        'payment_method',
        'receipt_number',
        'payment_collected_by',
    ];

    /**
     * Thẻ của user (một-một); eager load nhẹ cho API.
     */
    public function getCardForUser(User $user): ?LibraryCard
    {
        return $user->libraryCard()
            ->with([
                'faculty:id,code,name',
                'period:id,code,name,start_year,end_year',
                'payment.collector:id,name',
            ])
            ->first();
    }

    /**
     * Danh sách thẻ cho admin/thủ thư.
     *
     * @return LengthAwarePaginator<int, LibraryCard>
     */
    public function indexForAdmin(?string $workflowStatus, ?string $keyword, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = LibraryCard::query()
            ->with([
                'user:id,code,name,email,user_type',
                'faculty:id,code,name',
                'department:id,code,name',
                'period:id,code,name,start_year,end_year',
                'payment.collector:id,name',
            ]);

        if ($workflowStatus !== null && $workflowStatus !== '') {
            $query->where('workflow_status', $workflowStatus);
        }

        if ($keyword !== null && $keyword !== '') {
            $this->applyKeywordSearch($query, $keyword);
        }

        return $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();
    }

    public function getForAdminDetail(LibraryCard $libraryCard): LibraryCard
    {
        return $libraryCard->load([
            'user:id,code,name,email,phone,user_type',
            'faculty:id,code,name',
            'department:id,code,name',
            'period:id,code,name,start_year,end_year',
            'reviewer:id,name',
            'issuer:id,name',
            'payment.collector:id,name',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCard(LibraryCard $libraryCard, array $data): LibraryCard
    {
        return DB::transaction(function () use ($libraryCard, $data) {
            $paymentPatch = Arr::only($data, self::PAYMENT_ATTRIBUTE_KEYS);
            $cardData = Arr::except($data, self::PAYMENT_ATTRIBUTE_KEYS);

            if ($cardData !== []) {
                $libraryCard->update($cardData);
            }

            if ($paymentPatch !== []) {
                $libraryCard->payment()->updateOrCreate(
                    ['library_card_id' => $libraryCard->id],
                    $paymentPatch
                );
            }

            return $this->getForAdminDetail($libraryCard->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LibraryCard
    {
        $paymentPatch = Arr::only($data, self::PAYMENT_ATTRIBUTE_KEYS);
        $cardData = Arr::except($data, self::PAYMENT_ATTRIBUTE_KEYS);

        return DB::transaction(function () use ($cardData, $paymentPatch) {
            /** @var LibraryCard $card */
            $card = LibraryCard::query()->create($cardData);
            if ($paymentPatch !== []) {
                $card->payment()->create(array_merge(
                    ['library_card_id' => $card->id],
                    $paymentPatch
                ));
            }

            return $this->getForAdminDetail($card);
        });
    }

    /**
     * Tìm theo keyword: các cột thẻ + niên khóa + bảng thanh toán.
     */
    private function applyKeywordSearch(Builder $query, string $keyword): void
    {
        $like = '%'.$this->escapeLike($keyword).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('card_number', 'like', $like)
                ->orWhere('full_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('class_code', 'like', $like)
                ->orWhere('external_organization', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhereHas('payment', function (Builder $pq) use ($like) {
                    $pq->where('payment_status', 'like', $like)
                        ->orWhere('receipt_number', 'like', $like)
                        ->orWhere('payment_method', 'like', $like);
                })
                ->orWhereHas('period', function (Builder $pq) use ($like) {
                    $pq->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                });
        });
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
