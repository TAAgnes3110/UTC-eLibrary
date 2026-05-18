import client from '@/api/axios';

/**
 * Đồng bộ JWT từ session Inertia khi chưa có token API (tránh gọi lặp mỗi lần đổi trang).
 */
export async function syncApiTokenFromSession(authUser) {
    if (typeof window === 'undefined' || !authUser?.id) {
        return;
    }
    if (localStorage.getItem('token')) {
        return;
    }

    try {
        const response = await client.post('/auth/session-token');
        const token = response?.data?.token ?? response?.data?.data?.token ?? null;
        if (token) {
            localStorage.setItem('token', token);
        }
    } catch {
        // Session chưa sẵn sàng — API vẫn có thể dùng cookie (statefulApi).
    }
}
