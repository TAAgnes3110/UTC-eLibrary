import axios from 'axios';
import { ensureSanctumCsrfCookie, getApiCsrfHeaders } from '@/utils/apiCsrf';

const SESSION_TOKEN_COOLDOWN_MS = 60_000;
let sessionTokenInFlight = null;
let lastSessionTokenAt = 0;

export function extractApiTokenFromResponse(data) {
    return data?.token ?? data?.data?.token ?? null;
}

/**
 * Cấp JWT từ session Inertia — có cooldown để tránh 429 (throttle refresh).
 */
export async function fetchSessionApiToken({ force = false } = {}) {
    if (typeof window === 'undefined') {
        return null;
    }

    const existing = localStorage.getItem('token');
    if (!force && existing && Date.now() - lastSessionTokenAt < SESSION_TOKEN_COOLDOWN_MS) {
        return existing;
    }

    if (sessionTokenInFlight) {
        return sessionTokenInFlight;
    }

    sessionTokenInFlight = (async () => {
        try {
            await ensureSanctumCsrfCookie();
            const res = await axios.post('/api/v1/auth/session-token', null, {
                withCredentials: true,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    domain: window.location.origin,
                    ...getApiCsrfHeaders(),
                },
            });
            const token = extractApiTokenFromResponse(res.data);
            if (token) {
                localStorage.setItem('token', token);
            }
            lastSessionTokenAt = Date.now();
            return token;
        } catch {
            return null;
        } finally {
            sessionTokenInFlight = null;
        }
    })();

    return sessionTokenInFlight;
}

let refreshInFlight = null;
let lastRefreshAt = 0;
const REFRESH_COOLDOWN_MS = 60_000;

export async function refreshStoredApiToken() {
    if (typeof window === 'undefined') {
        return null;
    }

    const oldToken = localStorage.getItem('token');
    if (!oldToken) {
        return null;
    }

    if (Date.now() - lastRefreshAt < REFRESH_COOLDOWN_MS) {
        return oldToken;
    }

    if (refreshInFlight) {
        return refreshInFlight;
    }

    refreshInFlight = (async () => {
        try {
            const res = await axios.post('/api/v1/auth/refresh', null, {
                withCredentials: true,
                headers: { Authorization: `Bearer ${oldToken}` },
            });
            const token = extractApiTokenFromResponse(res.data);
            if (token) {
                localStorage.setItem('token', token);
            }
            lastRefreshAt = Date.now();
            return token;
        } catch {
            return null;
        } finally {
            refreshInFlight = null;
        }
    })();

    return refreshInFlight;
}

/** Chỉ lấy token khi chưa có — tránh spam session-token trước mỗi Lưu. */
export async function ensureApiToken() {
    if (typeof window !== 'undefined' && localStorage.getItem('token')) {
        return localStorage.getItem('token');
    }

    return fetchSessionApiToken({ force: true });
}
