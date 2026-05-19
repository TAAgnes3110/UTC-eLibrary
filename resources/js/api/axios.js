import axios from 'axios';
import { extractApiTokenFromResponse, fetchSessionApiToken, refreshStoredApiToken } from '@/utils/ensureApiToken';
import { ensureSanctumCsrfCookie, getApiCsrfHeaders } from '@/utils/apiCsrf';

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
        const token = localStorage.getItem('token');
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

let isRefreshing = false;
let refreshPromise = null;

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
            console.log('response.headers', safeJson(response?.headers));
            console.groupEnd();
        }

        sanitizeApiErrorPayloadForUser(error);

        if (!response || response.status !== 401) {
            return Promise.reject(error);
        }

        const reqPath = (originalRequest.url || '').split('?')[0];
        if (/^\/auth\/(login|register|verify-otp|reset-password|resend-otp)/.test(reqPath)) {
            return Promise.reject(error);
        }

        const isMultipartBody = originalRequest.data instanceof FormData;

        if (originalRequest._retry) {
            return Promise.reject(error);
        }
        originalRequest._retry = true;

        const retryWithoutBearer = async () => {
            if (originalRequest._sessionRetry) {
                return null;
            }
            originalRequest._sessionRetry = true;
            originalRequest.skipBearerAuth = true;
            if (originalRequest.headers && typeof originalRequest.headers === 'object') {
                delete originalRequest.headers.Authorization;
                delete originalRequest.headers.authorization;
            }
            try {
                return await client(originalRequest);
            } catch {
                return null;
            }
        };

        const retryWithFreshToken = async (token) => {
            if (!token || originalRequest._tokenRetry) {
                return null;
            }
            originalRequest._tokenRetry = true;
            originalRequest.skipBearerAuth = false;
            localStorage.setItem('token', token);
            originalRequest.headers = originalRequest.headers || {};
            originalRequest.headers.Authorization = `Bearer ${token}`;
            try {
                return await client(originalRequest);
            } catch {
                return null;
            }
        };

        const resolveFreshToken = async () => {
            const fromSession = await fetchSessionApiToken();
            if (fromSession) {
                return fromSession;
            }
            return refreshStoredApiToken();
        };

        try {
            if (isRefreshing && refreshPromise) {
                const newToken = await refreshPromise;
                if (newToken) {
                    if (isMultipartBody) {
                        localStorage.setItem('token', newToken);
                        const multipartError = new Error(
                            'Phiên API đã được làm mới. Vui lòng bấm Lưu lại để upload file.'
                        );
                        multipartError.response = response;
                        multipartError.config = originalRequest;
                        return Promise.reject(multipartError);
                    }
                    const tokenResponse = await retryWithFreshToken(newToken);
                    if (tokenResponse) {
                        return tokenResponse;
                    }
                }
                const sessionResponse = await retryWithoutBearer();
                if (sessionResponse) {
                    return sessionResponse;
                }
                throw error;
            }

            // Upload multipart: không retry FormData — cấp token mới để user bấm Lưu lại.
            if (isMultipartBody) {
                const refreshed = await resolveFreshToken();
                const multipartError = new Error(
                    refreshed
                        ? 'Phiên API đã được làm mới. Vui lòng bấm Lưu lại để upload file.'
                        : 'Phiên đăng nhập hết hạn khi upload file. Đăng nhập lại rồi bấm Lưu.'
                );
                multipartError.response = response;
                multipartError.config = originalRequest;
                return Promise.reject(multipartError);
            }

            // SPA Inertia: thử cookie session trước (không gửi bearer hết hạn).
            const sessionResponse = await retryWithoutBearer();
            if (sessionResponse) {
                return sessionResponse;
            }

            const freshToken = await resolveFreshToken();
            if (freshToken) {
                const tokenResponse = await retryWithFreshToken(freshToken);
                if (tokenResponse) {
                    return tokenResponse;
                }
            }

            const oldToken = localStorage.getItem('token');
            if (!oldToken) {
                throw error;
            }

            isRefreshing = true;
            refreshPromise = axios
                .post('/api/v1/auth/refresh', null, {
                    withCredentials: true,
                    headers: { Authorization: `Bearer ${oldToken}` },
                })
                .then((res) => extractApiTokenFromResponse(res.data))
                .catch(() => null);

            const newToken = await refreshPromise;

            if (newToken) {
                localStorage.setItem('token', newToken);
                const refreshedResponse = await retryWithFreshToken(newToken);
                if (refreshedResponse) {
                    return refreshedResponse;
                }
            }

            localStorage.removeItem('token');
            localStorage.removeItem('user');

            throw error;
        } catch (e) {
            if (!isMultipartBody) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            }
            const path = typeof window !== 'undefined' ? window.location.pathname : '';
            const onAuthPage = path.startsWith('/login') || path.startsWith('/register');
            const triedSession = Boolean(originalRequest._sessionRetry);
            if (
                typeof window !== 'undefined'
                && !onAuthPage
                && !isMultipartBody
                && triedSession
            ) {
                window.location.href = '/login';
            }
            return Promise.reject(e);
        } finally {
            isRefreshing = false;
            refreshPromise = null;
        }
    }
);

if (typeof window !== 'undefined') {
    window.axios = client;
}

export default client;
