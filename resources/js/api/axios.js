import axios from 'axios';
import { ensureSanctumCsrfCookie, getApiCsrfHeaders } from '@/utils/apiCsrf';
import { clearLegacyAuthStorage, getStoredApiToken } from '@/utils/apiAuthStorage';

function isApiDebugEnabled() {
    if (typeof window === 'undefined') return false;
    return localStorage.getItem('api_debug') === '1';
}

function safeJson(obj) {
    try {
        return JSON.parse(JSON.stringify(obj));
    } catch {
        return obj;
    }
}

/** Avoid showing DB/host/SQL details in UI toasts if the server ever returns them. */
function sanitizeApiErrorPayloadForUser(error) {
    const data = error?.response?.data;
    if (!data || typeof data !== 'object') return;
    const msg = data.messages;
    if (typeof msg !== 'string') return;
    if (
        /SQLSTATE|Unknown column|\(Connection:\s*mysql|Host:\s*\d|Database:\s*\w|SQL:\s*(insert|update|delete|select)/i.test(
            msg
        )
    ) {
        data.messages = 'Không thể thực hiện thao tác. Vui lòng thử lại sau.';
    }
}

const client = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

client.interceptors.request.use(
    async (config) => {
        if (isApiDebugEnabled()) {
            config.metadata = config.metadata || {};
            config.metadata.startTime = performance?.now?.() ?? Date.now();
            const method = (config.method || 'GET').toUpperCase();
            const url = `${config.baseURL || ''}${config.url || ''}`;
            console.groupCollapsed(`[API][REQ] ${method} ${url}`);
            console.log('headers', safeJson(config.headers || {}));
            console.log('params', safeJson(config.params || {}));
            console.log('data', safeJson(config.data || null));
            console.groupEnd();
        }
        if (config.data instanceof FormData) {
            if (config.headers && typeof config.headers === 'object') {
                delete config.headers['Content-Type'];
                delete config.headers['content-type'];
            }
        }
        const method = (config.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            await ensureSanctumCsrfCookie();
            if (config.headers && typeof config.headers === 'object') {
                Object.assign(config.headers, getApiCsrfHeaders());
            }
        }
        const isAdminSpa =
            typeof window !== 'undefined' && window.location.pathname.startsWith('/admin');

        // Trang admin Inertia: luôn dùng cookie session — JWT localStorage hay gây 401/429.
        if (isAdminSpa && !config.forceBearerAuth) {
            config.skipBearerAuth = true;
        }

        if (isAdminSpa) {
            clearLegacyAuthStorage();
        }

        const token = getStoredApiToken();
        if (!config.skipBearerAuth && token) {
            config.headers.Authorization = `Bearer ${token}`;
        } else if (config.skipBearerAuth && config.headers && typeof config.headers === 'object') {
            delete config.headers.Authorization;
            delete config.headers.authorization;
        }
        if (typeof window !== 'undefined') {
            config.headers['domain'] = config.headers['domain'] ?? window.location.origin;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

client.interceptors.response.use(
    (response) => {
        if (isApiDebugEnabled()) {
            const cfg = response.config || {};
            const method = (cfg.method || 'GET').toUpperCase();
            const url = `${cfg.baseURL || ''}${cfg.url || ''}`;
            const start = cfg.metadata?.startTime;
            const end = performance?.now?.() ?? Date.now();
            const ms = start ? Math.round(end - start) : null;
            console.groupCollapsed(`[API][RES] ${response.status} ${method} ${url}${ms != null ? ` (${ms}ms)` : ''}`);
            console.log('data', safeJson(response.data));
            console.groupEnd();
        }
        return response;
    },
    async (error) => {
        const { response, config } = error || {};
        const originalRequest = config || {};

        if (isApiDebugEnabled()) {
            const method = (originalRequest.method || 'GET').toUpperCase();
            const url = `${originalRequest.baseURL || ''}${originalRequest.url || ''}`;
            const start = originalRequest.metadata?.startTime;
            const end = performance?.now?.() ?? Date.now();
            const ms = start ? Math.round(end - start) : null;
            console.groupCollapsed(
                `[API][ERR] ${response?.status ?? 'NO_RESPONSE'} ${method} ${url}${ms != null ? ` (${ms}ms)` : ''}`
            );
            console.log('message', error?.message);
            console.log('response.data', safeJson(response?.data));
            console.groupEnd();
        }

        sanitizeApiErrorPayloadForUser(error);

        if (!response) {
            return Promise.reject(error);
        }

        if (response.status === 429) {
            const rateError = new Error(
                'Quá nhiều yêu cầu trong thời gian ngắn. Đợi khoảng 1 phút rồi thử lại.'
            );
            rateError.response = response;
            return Promise.reject(rateError);
        }

        if (response.status !== 401) {
            return Promise.reject(error);
        }

        const reqPath = (originalRequest.url || '').split('?')[0];
        if (/^\/auth\/(login|register|verify-otp|reset-password|resend-otp|refresh|session-token)/.test(reqPath)) {
            return Promise.reject(error);
        }

        if (originalRequest._retry || originalRequest._sessionRetry) {
            return Promise.reject(error);
        }

        const isMultipartBody = originalRequest.data instanceof FormData;
        const isAdminSpa =
            typeof window !== 'undefined' && window.location.pathname.startsWith('/admin');

        // FormData không retry được (body đã consume) — báo lỗi rõ, không gửi request rỗng.
        if (isMultipartBody) {
            const multipartError = new Error(
                isAdminSpa
                    ? 'Phiên đăng nhập admin không hợp lệ. Tải lại trang (F5), đăng nhập lại rồi bấm Lưu.'
                    : 'Phiên đăng nhập có thể đã hết hạn khi upload file. Tải lại trang (F5), đăng nhập lại rồi bấm Lưu.'
            );
            multipartError.response = response;
            return Promise.reject(multipartError);
        }

        originalRequest._retry = true;
        originalRequest._sessionRetry = true;
        originalRequest.skipBearerAuth = true;
        if (originalRequest.headers && typeof originalRequest.headers === 'object') {
            delete originalRequest.headers.Authorization;
            delete originalRequest.headers.authorization;
        }

        try {
            await ensureSanctumCsrfCookie();
            if (originalRequest.headers && typeof originalRequest.headers === 'object') {
                Object.assign(originalRequest.headers, getApiCsrfHeaders());
            }
            return await client(originalRequest);
        } catch (retryError) {
            return Promise.reject(retryError);
        }
    }
);

if (typeof window !== 'undefined') {
    window.axios = client;
}

export default client;
