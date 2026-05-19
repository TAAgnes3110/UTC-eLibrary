import { fetchSessionApiToken } from '@/utils/ensureApiToken';

/**
 * Đồng bộ JWT từ session Inertia (admin SPA) — luôn thử cấp token mới khi đã đăng nhập web.
 */
export async function syncApiTokenFromSession(authUser) {
    if (typeof window === 'undefined' || !authUser?.id) {
        return;
    }

    await fetchSessionApiToken();
}
