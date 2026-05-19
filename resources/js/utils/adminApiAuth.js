import client from '@/api/axios';
import { fetchSessionApiToken } from '@/utils/ensureApiToken';

function isUnauthorizedError(error) {
    return error?.response?.status === 401;
}

function isTooManyRequestsError(error) {
    return error?.response?.status === 429;
}

export function adminApiRateLimitMessage() {
    return 'Hệ thống tạm giới hạn yêu cầu đăng nhập API. Đợi khoảng 1 phút, tải lại trang (F5) rồi thử Lưu lại.';
}

/**
 * Một lần trước khi Lưu: chỉ cấp token nếu localStorage chưa có.
 */
export async function prepareAdminApiAuthOnce() {
    if (typeof window === 'undefined') {
        return null;
    }
    if (localStorage.getItem('token')) {
        return localStorage.getItem('token');
    }
    return fetchSessionApiToken({ force: true });
}

/**
 * Gọi API; 401 → xóa JWT cũ, thử lại bằng cookie session (không gọi refresh/session-token).
 */
export async function callWithSessionFallback(requestFn, sessionRequestFn) {
    try {
        return await requestFn();
    } catch (error) {
        if (isTooManyRequestsError(error)) {
            const rateError = new Error(adminApiRateLimitMessage());
            rateError.response = error.response;
            throw rateError;
        }
        if (!isUnauthorizedError(error) || typeof sessionRequestFn !== 'function') {
            throw error;
        }

        try {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        } catch {
            //
        }

        return sessionRequestFn();
    }
}

/** POST JSON qua cookie session (sau khi bỏ bearer hết hạn). */
export function sessionApiPost(url, payload) {
    return client.post(url, payload, { skipBearerAuth: true }).then((r) => r.data);
}

/** POST multipart qua cookie session. */
export function sessionApiPostForm(url, formData, config = {}) {
    return client
        .post(url, formData, {
            ...config,
            skipBearerAuth: true,
        })
        .then((r) => r.data);
}

/** FormData upload PDF tài liệu số (tạo mới mỗi lần — tránh body rỗng khi retry). */
export function buildDigitalAssetUploadFormData(file) {
    const digitalData = new FormData();
    digitalData.append('file', file);
    digitalData.append('is_primary', '1');
    digitalData.append('visibility', 'public');
    return digitalData;
}

export function uploadDigitalAssetViaSession(bookId, file) {
    return sessionApiPostForm(`/books/${bookId}/digital-assets`, buildDigitalAssetUploadFormData(file), {
        timeout: 300000,
    });
}

export function uploadBookCoverViaSession(bookId, coverFile) {
    const coverData = new FormData();
    coverData.append('book_cover', coverFile);
    return sessionApiPostForm(`/books/${bookId}/image`, coverData);
}
