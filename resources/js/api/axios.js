import axios from 'axios';

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

const client = axios.create({
    baseURL: '/api/v1',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

client.interceptors.request.use(
    (config) => {
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
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
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

        if (!response || response.status !== 401) {
            return Promise.reject(error);
        }

        const reqPath = (originalRequest.url || '').split('?')[0];
        if (/^\/auth\/(login|register|verify-otp|reset-password|resend-otp)/.test(reqPath)) {
            return Promise.reject(error);
        }

        if (originalRequest._retry) {
            return Promise.reject(error);
        }
        originalRequest._retry = true;

        try {
            if (isRefreshing && refreshPromise) {
                const newToken = await refreshPromise;
                if (newToken) {
                    originalRequest.headers = originalRequest.headers || {};
                    originalRequest.headers.Authorization = `Bearer ${newToken}`;
                    return client(originalRequest);
                }
                throw error;
            }

            isRefreshing = true;
            const oldToken = localStorage.getItem('token');
            if (!oldToken) throw error;

            refreshPromise = axios
                .post('/api/v1/auth/refresh', null, {
                    headers: { Authorization: `Bearer ${oldToken}` },
                })
                .then((res) => res.data?.token || null)
                .catch(() => null);

            const newToken = await refreshPromise;
            isRefreshing = false;
            refreshPromise = null;

            if (!newToken) {
                throw error;
            }

            localStorage.setItem('token', newToken);

            originalRequest.headers = originalRequest.headers || {};
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            return client(originalRequest);
        } catch (e) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/login')) {
                window.location.href = '/login';
            }
            return Promise.reject(e);
        }
    }
);

if (typeof window !== 'undefined') {
    window.axios = client;
}

export default client;
