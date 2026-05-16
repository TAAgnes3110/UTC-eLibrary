import client from '@/api/axios';

/**
 * Đồng bộ JWT vào localStorage khi đã đăng nhập Inertia (session) nhưng chưa có token API.
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
        // Session chưa sẵn sàng hoặc không có quyền — API vẫn có thể dùng cookie sau statefulApi.
    }
}
