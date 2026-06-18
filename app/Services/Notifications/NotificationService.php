<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NotificationService
{
    private const PER_PAGE = 50;

    /**
     * @param  array{
     *     recipient_type:string,
     *     recipient_id:int,
     *     type:NotificationType|string,
     *     title:string,
     *     message:string,
     *     severity?:NotificationSeverity|string|null,
     *     entity_type?:string|null,
     *     entity_id?:int|null,
     *     action_url?:string|null,
     *     meta?:array<string,mixed>|null,
     *     dedupe_key?:string|null
     * }  $data
     */
    public function notify(array $data): Notification
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload): Notification {
            $dedupeKey = (string) ($payload['dedupe_key'] ?? '');

            if ($dedupeKey !== '') {
                $existing = Notification::query()
                    ->where('dedupe_key', $dedupeKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing instanceof Notification) {
                    return $this->updateExistingByDedupe($existing, $payload);
                }
            }

            return $this->createNotification($payload);
        });
    }

    /**
     * @param  list<array<string,mixed>>  $items
     * @return Collection<int, Notification>
     */
    public function notifyMany(array $items): Collection
    {
        $created = new Collection;
        foreach ($items as $item) {
            $created->push($this->notify($item));
        }

        return $created;
    }

    /**
     * @param  array{
     *     unread_only?:bool,
     *     type?:NotificationType|string|null,
     *     severity?:NotificationSeverity|string|null,
     *     per_page?:int
     * }  $filters
     */
    public function listForRecipient(string $recipientType, int $recipientId, array $filters = []): LengthAwarePaginator
    {
        $this->assertRecipient($recipientType, $recipientId);

        $type = null;
        if (! empty($filters['type'])) {
            $type = $this->resolveTypeValue($filters['type']);
        }

        $severity = null;
        if (! empty($filters['severity'])) {
            $severity = $this->resolveSeverityValue($filters['severity']);
        }

        $perPage = $this->resolvePerPage((int) ($filters['per_page'] ?? self::PER_PAGE));

        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->when(($filters['unread_only'] ?? false) === true, fn ($query) => $query->whereNull('read_at'))
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($severity !== null, fn ($query) => $query->where('severity', $severity))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Đánh dấu đã đọc một thông báo theo recipient.
     */
    public function markAsRead(int $notificationId, string $recipientType, int $recipientId): int
    {
        $this->assertRecipient($recipientType, $recipientId);

        return Notification::query()
            ->whereKey($notificationId)
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Đánh dấu toàn bộ thông báo chưa đọc đã đọc theo recipient.
     */
    public function markAllAsRead(string $recipientType, int $recipientId): int
    {
        $this->assertRecipient($recipientType, $recipientId);

        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Xóa một thông báo thuộc về recipient (không tác động bản ghi của người khác).
     */
    public function deleteForRecipient(int $notificationId, string $recipientType, int $recipientId): int
    {
        $this->assertRecipient($recipientType, $recipientId);

        return Notification::query()
            ->whereKey($notificationId)
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->delete();
    }

    /**
     * Xóa toàn bộ thông báo của recipient.
     */
    public function deleteAllForRecipient(string $recipientType, int $recipientId): int
    {
        $this->assertRecipient($recipientType, $recipientId);

        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->delete();
    }

    /**
     * Lấy số lượng thông báo chưa đọc của recipient.
     */
    public function unreadCount(string $recipientType, int $recipientId): int
    {
        $this->assertRecipient($recipientType, $recipientId);

        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Tạo khóa dedupe cho thông báo có entity liên quan.
     *
     * @throws InvalidArgumentException
     */
    public function buildEntityDedupeKey(
        NotificationType|string $type,
        string $recipientType,
        int $recipientId,
        string $entityType,
        int $entityId
    ): string {
        $this->assertRecipient($recipientType, $recipientId);
        if ($entityType === '' || $entityId <= 0) {
            throw new InvalidArgumentException('Entity type/id không hợp lệ để tạo dedupe key.');
        }

        return implode(':', [
            $this->resolveTypeValue($type),
            $recipientType,
            $recipientId,
            $entityType,
            $entityId,
        ]);
    }

    /**
     * Tạo khóa dedupe cho thông báo gộp theo ngày.
     */
    public function buildOverdueDedupeKey(
        NotificationType|string $type,
        string $recipientType,
        int $recipientId,
        string $entityType,
        int $entityId,
        Carbon|string $dayBucket
    ): string {
        $bucket = $dayBucket instanceof Carbon
            ? $dayBucket->toDateString()
            : Carbon::parse($dayBucket)->toDateString();

        return implode(':', [
            $this->buildEntityDedupeKey($type, $recipientType, $recipientId, $entityType, $entityId),
            $bucket,
        ]);
    }

    /**
     * Normalize payload để tạo thông báo.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function normalizePayload(array $data): array
    {
        $recipientType = (string) ($data['recipient_type'] ?? '');
        $recipientId = (int) ($data['recipient_id'] ?? 0);
        $this->assertRecipient($recipientType, $recipientId);

        $title = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        if ($title === '' || $message === '') {
            throw new InvalidArgumentException('Title và message không được để trống.');
        }

        $entityType = isset($data['entity_type']) ? trim((string) $data['entity_type']) : null;
        $entityId = array_key_exists('entity_id', $data) && $data['entity_id'] !== null
            ? (int) $data['entity_id']
            : null;
        $actionUrl = isset($data['action_url']) ? trim((string) $data['action_url']) : null;
        $dedupeKey = isset($data['dedupe_key']) ? trim((string) $data['dedupe_key']) : null;
        $meta = $data['meta'] ?? null;
        if ($meta !== null && ! is_array($meta)) {
            throw new InvalidArgumentException('Meta phải là array hoặc null.');
        }

        return [
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => $this->resolveTypeValue($data['type'] ?? ''),
            'title' => $title,
            'message' => $message,
            'severity' => $this->resolveSeverityValue($data['severity'] ?? NotificationSeverity::default()),
            'entity_type' => $entityType !== '' ? $entityType : null,
            'entity_id' => $entityId !== null && $entityId > 0 ? $entityId : null,
            'action_url' => $actionUrl !== '' ? $actionUrl : null,
            'meta' => $meta,
            'dedupe_key' => $dedupeKey !== '' ? $dedupeKey : null,
        ];
    }

    /**
     * Cập nhật bản ghi đã có cùng dedupe_key (kể cả đã đọc) — tránh vi phạm unique trên notifications.dedupe_key.
     *
     * @param  array<string,mixed>  $payload
     */
    private function updateExistingByDedupe(Notification $existing, array $payload): Notification
    {
        $existing->fill([
            'recipient_type' => $payload['recipient_type'],
            'recipient_id' => $payload['recipient_id'],
            'type' => $payload['type'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'severity' => $payload['severity'],
            'entity_type' => $payload['entity_type'],
            'entity_id' => $payload['entity_id'],
            'action_url' => $payload['action_url'],
            'meta' => $payload['meta'],
        ]);
        $existing->read_at = null;
        $existing->save();

        return $existing->fresh();
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function createNotification(array $payload): Notification
    {
        /** @var Notification $notification */
        $notification = Notification::query()->create($payload);

        return $notification;
    }

    /**
     * Kiểm tra recipient type và recipient id hợp lệ.
     *
     * @throws InvalidArgumentException
     */
    private function assertRecipient(string $recipientType, int $recipientId): void
    {
        if (! in_array($recipientType, [Notification::RECIPIENT_ADMIN, Notification::RECIPIENT_USER], true)) {
            throw new InvalidArgumentException('Recipient type không hợp lệ.');
        }
        if ($recipientId <= 0) {
            throw new InvalidArgumentException('Recipient id phải lớn hơn 0.');
        }
    }

    private function resolveTypeValue(NotificationType|string $type): string
    {
        if ($type instanceof NotificationType) {
            return $type->value;
        }

        $resolved = NotificationType::tryFrom((string) $type);
        if (! $resolved instanceof NotificationType) {
            throw new InvalidArgumentException('Notification type không hợp lệ.');
        }

        return $resolved->value;
    }

    /**
     * Chuyển đổi severity value từ enum hoặc string thành string.
     *
     * @throws InvalidArgumentException
     */
    private function resolveSeverityValue(NotificationSeverity|string|null $severity): string
    {
        if ($severity instanceof NotificationSeverity) {
            return $severity->value;
        }
        if ($severity === null || $severity === '') {
            return NotificationSeverity::default()->value;
        }

        $resolved = NotificationSeverity::tryFrom((string) $severity);
        if (! $resolved instanceof NotificationSeverity) {
            throw new InvalidArgumentException('Notification severity không hợp lệ.');
        }

        return $resolved->value;
    }

    /**
     * Giới hạn số lượng thông báo trên mỗi trang.
     */
    private function resolvePerPage(int $perPage): int
    {
        return min(max($perPage, 1), 100);
    }
}
