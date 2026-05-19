import { fetchSessionApiToken } from '@/utils/ensureApiToken';

/**
 * Đồng bộ JWT từ session Inertia khi chưa có token API (tránh gọi lặp → 429).
 */
export async function syncApiTokenFromSession(authUser) {
    if (typeof window === 'undefined' || !authUser?.id) {
        return;
    }

    if (localStorage.getItem('token')) {
        return;
    }

    await fetchSessionApiToken({ force: true });
}
