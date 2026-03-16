import axios from 'axios';

function getDefaultPeriod() {
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_API_PERIOD) {
        return import.meta.env.VITE_API_PERIOD;
    }
    const y = new Date().getFullYear();
    return `${y}-${y + 1}`;
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
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        if (typeof window !== 'undefined') {
            config.headers['domain'] = config.headers['domain'] ?? window.location.origin;
            config.headers['period'] = config.headers['period'] ?? getDefaultPeriod();
        }
        return config;
    },
    (error) => Promise.reject(error)
);

let isRefreshing = false;
let refreshPromise = null;

client.interceptors.response.use(
    (response) => response,
    async (error) => {
        const { response, config } = error || {};
        const originalRequest = config || {};

        if (!response || response.status !== 401) {
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

// Backward compat: Pages đang dùng window.axios
if (typeof window !== 'undefined') {
    window.axios = client;
}

export default client;
