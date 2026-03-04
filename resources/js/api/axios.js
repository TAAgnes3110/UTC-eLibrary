/**
 * Axios instance: baseURL /api/v1, interceptors (token, 401).
 * Gán vào window.axios để code cũ vẫn dùng được; module mới import từ đây.
 */
import axios from 'axios';

/** Năm học mặc định (vd: 2025-2026). Có thể override bằng env VITE_API_PERIOD nếu build-time cần. */
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

client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            if (!window.location.pathname.startsWith('/login')) {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

// Backward compat: Pages đang dùng window.axios
if (typeof window !== 'undefined') {
    window.axios = client;
}

export default client;
