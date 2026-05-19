import axios from 'axios';

let csrfCookiePromise = null;

/**
 * Đọc CSRF từ cookie XSRF-TOKEN hoặc meta (layout Inertia).
 */
export function getApiCsrfHeaders() {
    if (typeof document === 'undefined') {
        return {};
    }

    const row = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='));
    if (row) {
        const val = decodeURIComponent(row.split('=').slice(1).join('='));
        return { 'X-XSRF-TOKEN': val };
    }

    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta?.content) {
        return { 'X-CSRF-TOKEN': meta.content };
    }

    return {};
}

/**
 * Sanctum SPA: lấy cookie CSRF trước POST/PUT/PATCH/DELETE tới /api/*.
 */
export function ensureSanctumCsrfCookie() {
    if (typeof window === 'undefined') {
        return Promise.resolve();
    }

    if (!csrfCookiePromise) {
        csrfCookiePromise = axios
            .get('/sanctum/csrf-cookie', {
                withCredentials: true,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .catch(() => {});
    }

    return csrfCookiePromise;
}
