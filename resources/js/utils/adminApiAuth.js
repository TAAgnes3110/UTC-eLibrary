import client from '@/api/axios';
import { ensureSanctumCsrfCookie } from '@/utils/apiCsrf';
import { clearClientApiCredentials } from '@/utils/apiAuthStorage';

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
 * Trang admin chỉ dùng cookie session — xóa JWT cũ và xác minh /auth/user trước khi Lưu/upload.
 */
export async function ensureAdminWebSession() {
    if (typeof window === 'undefined') {
        return;
    }
    clearClientApiCredentials();
    await ensureSanctumCsrfCookie();
    await client.get('/auth/user', { skipBearerAuth: true });
}

/** @deprecated Dùng ensureAdminWebSession — admin không dùng JWT localStorage. */
export async function prepareAdminApiAuthOnce() {
    await ensureAdminWebSession();
    return null;
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

        clearClientApiCredentials();

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

function appendBookPayloadToFormData(formData, payload) {
    Object.entries(payload).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }
        if (typeof value === 'object') {
            formData.append(key, JSON.stringify(value));
        } else {
            formData.append(key, String(value));
        }
    });
}

/** FormData tạo/sửa tài liệu số atomic (metadata + PDF + ảnh bìa). */
export function buildDigitalBookFormData(payload, { pdfFile = null, coverFile = null } = {}) {
    const formData = new FormData();
    appendBookPayloadToFormData(formData, payload);
    if (pdfFile instanceof File) {
        formData.append('file', pdfFile);
        formData.append('is_primary', '1');
        formData.append('visibility', 'public');
    }
    if (coverFile instanceof File) {
        formData.append('book_cover', coverFile);
    }
    return formData;
}

/** POST /books/digital — một transaction phía server. */
export function createDigitalBookViaSession(payload, pdfFile, coverFile = null) {
    return sessionApiPostForm(
        '/books/digital',
        buildDigitalBookFormData(payload, { pdfFile, coverFile }),
        { timeout: 300000 }
    );
}

/**
 * Cập nhật tài liệu số atomic — dùng POST multipart (PUT + file hay lỗi trên một số PHP/proxy).
 */
export function updateDigitalBookViaSession(bookId, payload, pdfFile = null, coverFile = null) {
    return sessionApiPostForm(
        `/books/${bookId}/digital`,
        buildDigitalBookFormData(payload, { pdfFile, coverFile }),
        { timeout: 300000 }
    );
}

export function sessionApiPut(url, payload) {
    return client.put(url, payload, { skipBearerAuth: true }).then((r) => r.data);
}

export function sessionApiDelete(url) {
    return client.delete(url, { skipBearerAuth: true }).then((r) => r.data);
}

/**
 * Tạo tài liệu số mới mà upload PDF/ảnh fail → xóa bản ghi tạm (tránh “Chưa có file” trong DB).
 */
export async function rollbackDigitalBookCreate(bookId) {
    if (!bookId) {
        return false;
    }
    try {
        await sessionApiDelete(`/books/${bookId}`);
        return true;
    } catch {
        return false;
    }
}
