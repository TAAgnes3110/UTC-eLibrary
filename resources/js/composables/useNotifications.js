import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import apiClient from '@/api/axios';
import { toast } from '@/store/toast';

function formatRelativeTime(isoDate) {
    if (!isoDate) return '';
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return '';

    const diffMs = date.getTime() - Date.now();
    const absSeconds = Math.abs(Math.round(diffMs / 1000));
    const rtf = new Intl.RelativeTimeFormat('vi', { numeric: 'auto' });

    if (absSeconds < 60) return rtf.format(Math.round(diffMs / 1000), 'second');
    const absMinutes = Math.abs(Math.round(diffMs / 60000));
    if (absMinutes < 60) return rtf.format(Math.round(diffMs / 60000), 'minute');
    const absHours = Math.abs(Math.round(diffMs / 3600000));
    if (absHours < 24) return rtf.format(Math.round(diffMs / 3600000), 'hour');
    const absDays = Math.abs(Math.round(diffMs / 86400000));
    return rtf.format(Math.round(diffMs / 86400000), 'day');
}

function toArrayItems(payload) {
    const items = payload?.items;
    if (Array.isArray(items)) return items;
    if (Array.isArray(items?.data)) return items.data;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
}

function normalizeNotification(item) {
    const readAt = item?.read_at ?? null;
    const createdAt = item?.created_at ?? null;

    return {
        id: Number(item?.id ?? 0),
        type: String(item?.type ?? ''),
        title: String(item?.title ?? ''),
        message: String(item?.message ?? ''),
        severity: String(item?.severity ?? 'info'),
        actionUrl: item?.action_url ? String(item.action_url) : null,
        meta: item?.meta && typeof item.meta === 'object' ? item.meta : null,
        readAt,
        createdAt,
        read: readAt !== null,
        time: formatRelativeTime(createdAt),
    };
}

/**
 * @param {{ pollIntervalMs?: number, refetchOnVisibility?: boolean }} [options]
 */
export function useNotifications(options = {}) {
    const pollIntervalMs = Number(options.pollIntervalMs ?? 0) || 0;
    const refetchOnVisibility = Boolean(options.refetchOnVisibility ?? false);

    const page = usePage();
    const user = computed(() => page.props.auth?.user ?? null);
    const notifications = ref([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    const markingAll = ref(false);
    const markingIds = ref(new Set());
    const deletingAll = ref(false);
    const deletingIds = ref(new Set());

    const hasUnread = computed(() => unreadCount.value > 0);

    async function fetchNotifications() {
        if (!user.value?.id || loading.value) return;

        loading.value = true;
        try {
            const response = await apiClient.get('/me/notifications', {
                params: { per_page: 20 },
            });
            const payload = response?.data?.data ?? {};
            notifications.value = toArrayItems(payload)
                .map(normalizeNotification)
                .filter((item) => item.id > 0);
            unreadCount.value = Number(payload?.unread_count ?? 0);
        } catch (error) {
            notifications.value = [];
            unreadCount.value = 0;
            if (error?.response?.status !== 401) {
                toast.error('Không tải được thông báo. Vui lòng thử lại.', { title: 'Thông báo' });
            }
        } finally {
            loading.value = false;
        }
    }

    async function markAsRead(notificationId) {
        const id = Number(notificationId);
        if (!Number.isInteger(id) || id <= 0 || markingIds.value.has(id)) return;

        const target = notifications.value.find((item) => item.id === id);
        if (!target || target.read) {
            return;
        }

        markingIds.value.add(id);
        try {
            const response = await apiClient.post(`/me/notifications/${id}/read`);
            target.read = true;
            target.readAt = new Date().toISOString();
            unreadCount.value = Number(response?.data?.data?.unread_count ?? Math.max(0, unreadCount.value - 1));
        } catch (error) {
            toast.error(error?.response?.data?.messages || 'Không thể cập nhật trạng thái đã đọc.', { title: 'Thông báo' });
        } finally {
            markingIds.value.delete(id);
        }
    }

    async function markAllAsRead() {
        if (markingAll.value || unreadCount.value <= 0) return;

        markingAll.value = true;
        try {
            const response = await apiClient.post('/me/notifications/read-all');
            notifications.value = notifications.value.map((item) => ({
                ...item,
                read: true,
                readAt: item.readAt ?? new Date().toISOString(),
            }));
            unreadCount.value = Number(response?.data?.data?.unread_count ?? 0);
        } catch (error) {
            toast.error(error?.response?.data?.messages || 'Không thể đánh dấu toàn bộ đã đọc.', { title: 'Thông báo' });
        } finally {
            markingAll.value = false;
        }
    }

    async function deleteNotification(notificationId) {
        const id = Number(notificationId);
        if (!Number.isInteger(id) || id <= 0 || deletingIds.value.has(id)) return;

        deletingIds.value.add(id);
        try {
            const response = await apiClient.post(`/me/notifications/${id}/delete`);
            notifications.value = notifications.value.filter((item) => item.id !== id);
            unreadCount.value = Number(response?.data?.data?.unread_count ?? unreadCount.value);
        } catch (error) {
            toast.error(error?.response?.data?.messages || 'Không thể xóa thông báo.', { title: 'Thông báo' });
        } finally {
            deletingIds.value.delete(id);
        }
    }

    async function deleteAllNotifications() {
        if (deletingAll.value || !notifications.value.length) return;
        if (!window.confirm('Xóa toàn bộ thông báo trong danh sách này?')) return;

        deletingAll.value = true;
        try {
            await apiClient.post('/me/notifications/delete-all');
            notifications.value = [];
            unreadCount.value = 0;
        } catch (error) {
            toast.error(error?.response?.data?.messages || 'Không thể xóa toàn bộ thông báo.', { title: 'Thông báo' });
        } finally {
            deletingAll.value = false;
        }
    }

    let pollTimer = null;
    let onVisibility = null;

    onMounted(() => {
        fetchNotifications();
        if (pollIntervalMs > 0) {
            pollTimer = setInterval(() => {
                fetchNotifications();
            }, pollIntervalMs);
        }
        if (refetchOnVisibility) {
            onVisibility = () => {
                if (!document.hidden) {
                    fetchNotifications();
                }
            };
            document.addEventListener('visibilitychange', onVisibility);
        }
    });

    onUnmounted(() => {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        if (onVisibility !== null) {
            document.removeEventListener('visibilitychange', onVisibility);
            onVisibility = null;
        }
    });

    return {
        notifications,
        unreadCount,
        hasUnread,
        loading,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        markingAll,
        markingIds,
        deleteNotification,
        deleteAllNotifications,
        deletingAll,
        deletingIds,
    };
}
