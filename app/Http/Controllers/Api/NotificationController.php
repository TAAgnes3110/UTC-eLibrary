<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleType;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyAllNotificationsRequest;
use App\Http\Requests\DestroyNotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::error('Bạn chưa đăng nhập.', 401);
        }

        $validated = $request->validate([
            'unread_only' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'max:120'],
            'severity' => ['nullable', 'string', 'in:info,warning,critical'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$recipientType, $recipientId] = $this->resolveRecipient($user);
        $filters = [
            'unread_only' => (bool) ($validated['unread_only'] ?? false),
            'type' => $validated['type'] ?? null,
            'severity' => $validated['severity'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];

        $paginator = $this->notificationService->listForRecipient($recipientType, $recipientId, $filters);
        $unreadCount = $this->notificationService->unreadCount($recipientType, $recipientId);

        return ApiResponse::success([
            'items' => $paginator->through(fn (Notification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'severity' => $notification->severity,
                'entity_type' => $notification->entity_type,
                'entity_id' => $notification->entity_id,
                'action_url' => $notification->action_url,
                'meta' => $notification->meta,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::error('Bạn chưa đăng nhập.', 401);
        }

        [$recipientType, $recipientId] = $this->resolveRecipient($user);
        $affected = $this->notificationService->markAsRead($notificationId, $recipientType, $recipientId);
        $unreadCount = $this->notificationService->unreadCount($recipientType, $recipientId);

        return ApiResponse::success([
            'marked' => $affected > 0,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::error('Bạn chưa đăng nhập.', 401);
        }

        [$recipientType, $recipientId] = $this->resolveRecipient($user);
        $affected = $this->notificationService->markAllAsRead($recipientType, $recipientId);

        return ApiResponse::success([
            'marked_count' => $affected,
            'unread_count' => 0,
        ]);
    }

    public function destroy(DestroyNotificationRequest $request, int $notificationId): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::error('Bạn chưa đăng nhập.', 401);
        }

        [$recipientType, $recipientId] = $this->resolveRecipient($user);
        $deleted = $this->notificationService->deleteForRecipient($notificationId, $recipientType, $recipientId);
        $unreadCount = $this->notificationService->unreadCount($recipientType, $recipientId);

        return ApiResponse::success([
            'deleted' => $deleted > 0,
            'unread_count' => $unreadCount,
        ]);
    }

    public function destroyAll(DestroyAllNotificationsRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::error('Bạn chưa đăng nhập.', 401);
        }

        [$recipientType, $recipientId] = $this->resolveRecipient($user);
        $deletedCount = $this->notificationService->deleteAllForRecipient($recipientType, $recipientId);

        return ApiResponse::success([
            'deleted_count' => $deletedCount,
            'unread_count' => 0,
        ]);
    }

    /**
     * @return array{0:string,1:int}
     */
    private function resolveRecipient(User $user): array
    {
        $roleValue = $user->user_type instanceof RoleType
            ? $user->user_type->value
            : (string) $user->user_type;

        if ($roleValue !== '' && in_array($roleValue, RoleType::staffRoles(), true)) {
            return [Notification::RECIPIENT_ADMIN, (int) $user->id];
        }

        return [Notification::RECIPIENT_USER, (int) $user->id];
    }
}
