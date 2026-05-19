import { fetchSessionApiToken, refreshStoredApiToken } from '@/utils/ensureApiToken';

/**
 * Chuẩn bị auth trước thao tác admin (form tài liệu số thường mất nhiều phút).
 * Ưu tiên JWT mới từ session web; nếu không được thì bỏ token cũ để API dùng cookie.
 */
export async function prepareAdminApiAuth() {
    if (typeof window === 'undefined') {
        return null;
    }

    const fromSession = await fetchSessionApiToken();
    if (fromSession) {
        return fromSession;
    }

    const refreshed = await refreshStoredApiToken();
    if (refreshed) {
        return refreshed;
    }

    try {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    } catch {
        //
    }

    return null;
}

function isUnauthorizedError(error) {
    return error?.response?.status === 401;
}

/**
 * Gọi API admin; nếu 401 (JWT hết hạn trong localStorage) thì xóa token và thử lại bằng session.
 */
export async function callWithAdminAuthRetry(requestFn) {
    await prepareAdminApiAuth();

    try {
        return await requestFn();
    } catch (error) {
        if (!isUnauthorizedError(error)) {
            throw error;
        }

        try {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        } catch {
            //
        }

        await fetchSessionApiToken();
        return await requestFn();
    }
}
